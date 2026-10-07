<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\AwardSessionRewards;
use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\User;
use App\Notifications\SessionClosedBySubstitute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * La sessione (P21): resoconto, presenze e ricompense si fanno qui, non nel
 * Pannello (D20). Le prenotazioni stanno in SessionBookingController.
 */
class GameSessionController extends Controller
{
    /** Il calendario delle sessioni (P20). */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', GameSession::class);

        // Un mese scritto storto nell'indirizzo ricade su quello corrente, niente 404.
        $mese = rescue(
            fn () => Carbon::createFromFormat('Y-m', (string) $request->query('mese'))->startOfMonth(),
            fn () => now()->startOfMonth(),
            report: false,
        );

        $sessions = GameSession::query()
            ->whereBetween('played_at', [$mese->copy(), $mese->copy()->endOfMonth()])
            ->with('campaign')
            ->orderBy('played_at')
            ->get();

        return view('sessions.index', [
            'mese' => $mese,
            'sessions' => $sessions,

            // Per giorno: nella stessa sera possono esserci due sessioni.
            'perGiorno' => $sessions->groupBy(fn (GameSession $s) => $s->played_at->toDateString()),

            // I prossimi eventi, indipendenti dal mese scelto.
            'events' => Event::published()->upcoming()->limit(4)->get(),
        ]);
    }

    public function show(GameSession $session): View
    {
        $this->authorize('view', $session);

        $session->load(['campaign.dm', 'recapWrittenBy', 'attendees.characters', 'quests', 'bookings']);

        $utente = auth()->user();
        $posti = $session->bookings()->holdingSeat()->with(['user', 'character'])->get();

        // La precedente e la successiva della stessa campagna, per data e non per numero.
        $precedente = GameSession::where('campaign_id', $session->campaign_id)
            ->where('played_at', '<', $session->played_at)
            ->orderByDesc('played_at')
            ->first();

        $prossima = GameSession::where('campaign_id', $session->campaign_id)
            ->where('played_at', '>', $session->played_at)
            ->orderBy('played_at')
            ->first();

        return view('sessions.show', [
            'session' => $session,
            'precedente' => $precedente,
            'prossima' => $prossima,

            // Gli eroi con PF, CA e monete, a qualsiasi DM (M16): i prenotati, o chi ha giocato la campagna.
            'eroi' => $utente->isDm()
                ? ($posti->isNotEmpty() ? $session->bookedCharacters() : $session->campaign->roster())
                : collect(),

            // Le prenotazioni, ospiti compresi: chi ha un posto, la fila, e il proprio.
            'posti' => $posti,
            'inAttesa' => $session->bookings()->waiting()->with('user')->get(),
            // Gli ospiti segnati presenti, per «Chi c'era» e per collegarli all'account.
            'ospitiPresenti' => $session->bookings()->whereNull('user_id')->where('guest_attended', true)->get(),
            'mioPosto' => $session->seatOf($utente),
            'mioPersonaggio' => $session->bookedCharacterOf($utente),
            'mieiPersonaggi' => $utente->characters()->alive()->orderBy('name')->get(),

            'combattimenti' => auth()->user()?->isDm()
                ? $session->encounters()->oldest()->get()
                : collect(),

            // Chi si può segnare presente (gli admin non giocano), solo a chi segna le presenze.
            'candidates' => auth()->user()->can('recordAttendance', $session)
                ? User::visibleToPlayers()->with('characters')->orderBy('name')->get()
                : collect(),
        ]);
    }

    /** Il resoconto (M13). Si può riscrivere: le correzioni arrivano dopo. */
    public function writeRecap(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('writeRecap', $session);

        $dati = $request->validate([
            'recap' => ['required', 'string', 'max:20000'],
        ]);

        app(WriteRecap::class)->handle($session, $request->user(), $dati['recap']);
        $this->avvisaIlTitolare($session, $request->user(), 'il resoconto');

        return back()->with('status', 'Resoconto salvato.');
    }

    /**
     * Le presenze (M14): `presenti[]`, `personaggi[id]` e `ospiti[]`. Si tiene il
     * personaggio solo di chi è spuntato: la tendina resta compilata anche dopo.
     */
    public function recordAttendance(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('recordAttendance', $session);

        $dati = $request->validate([
            'presenti' => ['array'],
            'presenti.*' => ['integer', Rule::exists('users', 'id')],
            'personaggi' => ['array'],
            'personaggi.*' => ['nullable', 'integer', Rule::exists('characters', 'id')],
            'ospiti' => ['array'],
            'ospiti.*' => ['integer'],
        ]);

        $presenze = collect($dati['presenti'] ?? [])
            ->mapWithKeys(fn (int $userId) => [
                $userId => $dati['personaggi'][$userId] ?? null,
            ]);

        try {
            app(RecordAttendance::class)->handle($session, $presenze);
        } catch (InvalidArgumentException $e) {
            // La tendina arriva dal browser e i browser si manomettono:
            // l'azione rifiuta il personaggio di un altro giocatore, e qui si
            // dice cosa non va invece di rispondere con un errore del server.
            return back()->with('error', $e->getMessage());
        }

        // Gli ospiti non hanno un account: la presenza sta sul loro posto.
        $ospiti = $session->bookings()->whereNull('user_id');
        $ospiti->clone()->update(['guest_attended' => false]);
        $ospiti->clone()->whereIn('id', $dati['ospiti'] ?? [])->update(['guest_attended' => true]);

        $this->avvisaIlTitolare($session, $request->user(), 'le presenze');

        return back()->with('status', 'Presenze salvate.');
    }

    /** Le ricompense di fine sessione: le dà chi può segnare le presenze, ai presenti. */
    public function awardRewards(Request $request, GameSession $session, AwardSessionRewards $rewards): RedirectResponse
    {
        $this->authorize('recordAttendance', $session);

        $dati = $request->validate([
            'coins' => ['required', 'array'],
            'coins.*' => ['nullable', 'integer', 'min:0', 'max:'.Coins::MAX],
            'reason' => ['required', 'string', 'max:120'],
        ], [
            'reason.required' => 'Scrivi il motivo: finisce nel Registro di ognuno.',
        ]);

        try {
            $chi = $rewards->handle($session, $request->user(), Coins::fromArray($dati['coins']), $dati['reason']);
        } catch (InvalidArgumentException|MarketException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->avvisaIlTitolare($session, $request->user(), 'le ricompense');

        return back()->with('status', 'Ricompense date a '.$chi->pluck('name')->join(', ', ' e ').'.');
    }

    private function avvisaIlTitolare(GameSession $session, User $chi, string $cosa): void
    {
        if ($session->isSubstitute($chi)) {
            $session->campaign->dm?->notify(new SessionClosedBySubstitute($session, $chi->name, $cosa));
        }
    }
}
