<?php

namespace App\Http\Controllers;

use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ScheduleQuest;
use App\Enums\QuestDifficulty;
use App\Enums\QuestOutcome;
use App\Exceptions\QuestUnavailableException;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * La quest (P19). Il giocatore dice «mi interessa»; ci si prenota alla
 * sessione. Il DM la mette in una sessione e la conclude raccontando com'è andata.
 */
class QuestController extends Controller
{
    /** Le quest aperte di tutte le campagne (P18); le concluse stanno nel Libro Mastro. */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quest::class);

        // Nel filtro solo le campagne con almeno una quest aperta.
        $campaigns = Campaign::query()
            ->whereHas('quests', fn ($query) => $query->active())
            ->orderBy('title')
            ->get();

        // Un filtro inesistente nell'indirizzo ricade su tutte, niente 404.
        $campagna = $campaigns->firstWhere('slug', $request->query('campagna'));
        $difficolta = QuestDifficulty::tryFrom((string) $request->query('difficolta'));

        $quests = Quest::query()
            ->active()
            ->when($campagna, fn ($query) => $query->whereBelongsTo($campagna, 'campaign'))
            ->when($difficolta, fn ($query) => $query->where('difficulty', $difficolta))
            ->with(['campaign', 'session'])
            ->withCount('interested')
            ->latest('id')
            ->get()
            // Prima quelle già in una sessione in programma, dalla più vicina; poi le altre, le più recenti in cima.
            ->sortBy(fn (Quest $quest) => $quest->isScheduled()
                ? [0, $quest->session->played_at->timestamp]
                : [1, -$quest->id])
            ->values();

        return view('quests.index', [
            'quests' => $quests,
            'campaigns' => $campaigns,
            'campagna' => $campagna,
            'difficolta' => $difficolta,
        ]);
    }

    public function show(Quest $quest): View
    {
        $this->authorize('view', $quest);

        $quest->load(['campaign.dm', 'session']);

        return view('quests.show', [
            'quest' => $quest,
            'interessati' => $quest->interested()->get(),
            'miInteressa' => $quest->isInterested(request()->user()),

            // Solo a chi può metterla in una sessione: quelle della sua campagna,
            // anche le già giocate per registrare il passato.
            'sessioni' => request()->user()->can('schedule', $quest)
                ? GameSession::where('campaign_id', $quest->campaign_id)->upcoming()->get()
                : collect(),
            'sessioniPassate' => request()->user()->can('schedule', $quest)
                ? GameSession::where('campaign_id', $quest->campaign_id)->past()->get()
                : collect(),
        ]);
    }

    /** «Mi interessa», e il suo contrario: lo stesso tasto. */
    public function interest(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('interest', $quest);

        $utente = $request->user();

        if ($quest->isInterested($utente)) {
            $quest->interested()->detach($utente);

            return back()->with('status', 'Non ti interessa più.');
        }

        $quest->interested()->attach($utente, ['joined_at' => now()]);

        return back()->with('status', 'Segnato. Quando il DM la mette in una sessione, ti arriverà un avviso.');
    }

    /** Mettere la quest in una sessione della campagna, o toglierla. */
    public function schedule(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('schedule', $quest);

        $dati = $request->validate([
            'game_session_id' => ['nullable', 'integer', Rule::exists('game_sessions', 'id')],
        ]);

        $sessione = filled($dati['game_session_id'] ?? null) ? GameSession::findOrFail($dati['game_session_id']) : null;
        $avvisa = $request->boolean('notify_players', true);

        try {
            app(ScheduleQuest::class)->handle($quest, $sessione, $avvisa);
        } catch (InvalidArgumentException|QuestUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', match (true) {
            $sessione === null => 'Quest tolta dalla sessione.',
            $sessione->isUpcoming() && $avvisa => 'Quest messa nella sessione: chi l\'aveva segnata ha ricevuto l\'avviso.',
            default => 'Quest messa nella sessione, senza avvisi.',
        });
    }

    /** Concludere la quest (M9); il racconto di com'è andata è facoltativo. */
    public function conclude(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('conclude', $quest);

        $dati = $request->validate([
            'outcome' => ['required', Rule::in([QuestOutcome::Completed->value, QuestOutcome::Closed->value])],
            'outcome_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        app(ConcludeQuest::class)->handle(
            $quest,
            QuestOutcome::from($dati['outcome']),
            $dati['outcome_notes'] ?? null,
        );

        return back()->with('status', 'Quest conclusa.');
    }
}
