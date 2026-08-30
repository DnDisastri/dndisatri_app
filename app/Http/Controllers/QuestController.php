<?php

namespace App\Http\Controllers;

use App\Actions\Quests\BookQuestSeat;
use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ConfirmQuestNight;
use App\Actions\Quests\PromoteFromWaitingList;
use App\Actions\Quests\WithdrawFromQuest;
use App\Enums\QuestDifficulty;
use App\Enums\QuestOutcome;
use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * L'incarico (P19) e tutto quello che ci si fa sopra.
 *
 * Un giocatore ha due gesti, e sono le uniche due cose che fa senza che
 * nessuno approvi niente: dichiarare che vorrebbe esserci, e tirarsi indietro.
 * Il posto glielo conferma il dungeon master quando decide che la serata si fa.
 *
 * Il dungeon master ne ha tre, e stanno qui e non nel Pannello perché si fanno
 * al tavolo (D20): confermare la serata, chiamare qualcuno dall'attesa e
 * chiudere l'incarico raccontando com'è andata.
 */
class QuestController extends Controller
{
    /**
     * L'elenco degli incarichi (P18).
     *
     * È la domanda «cosa posso fare stasera?», che non ha una campagna
     * precisa in testa: per questo esiste una pagina che le mescola tutte,
     * mentre P17 mostra solo quelle del suo tavolo.
     *
     * **Solo gli aperti.** Un incarico concluso non è una cosa che si può
     * fare, e il suo posto è l'archivio della campagna. Mescolare i finiti
     * qui vorrebbe dire scorrere la memoria del gruppo per trovare una serata
     * di stasera.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quest::class);

        // Solo le campagne che hanno davvero un incarico aperto: un filtro che
        // porta a un elenco vuoto è un filtro che non serve a niente.
        $campaigns = Campaign::query()
            ->whereHas('quests', fn ($query) => $query->active())
            ->orderBy('title')
            ->get();

        /*
         * Un filtro chiesto e inesistente non è un errore: è un indirizzo
         * vecchio, o una prova. Si ricade su tutti invece di dare 404, come
         * per la season delle campagne e per il mese del calendario.
         */
        $campagna = $campaigns->firstWhere('slug', $request->query('campagna'));
        $difficolta = QuestDifficulty::tryFrom((string) $request->query('difficolta'));

        $quests = Quest::query()
            ->active()
            ->when($campagna, fn ($query) => $query->whereBelongsTo($campagna, 'campaign'))
            ->when($difficolta, fn ($query) => $query->where('difficulty', $difficolta))
            ->with('campaign')
            ->latest('id')
            ->get()
            /*
             * L'ordine si decide in PHP e non in SQL. «Quanti posti mancano»
             * è una regola che vive già sul modello — `missingToMinimum()`,
             * `isFull()` — e riscriverla in una query la farebbe esistere in
             * due posti che possono divergere: è la stessa scelta della Home.
             *
             * Prima quelli a cui manca poco per partire, perché lì il proprio
             * sì cambia le cose; poi quelli che hanno ancora posto; per
             * ultimi i pieni, dove ci si può solo mettere in fila.
             */
            ->sortBy(fn (Quest $quest) => [
                match (true) {
                    $quest->isFull() => 2,
                    $quest->missingToMinimum() > 0 => 0,
                    default => 1,
                },
                $quest->missingToMinimum(),
            ])
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

        $quest->load('campaign.dm');

        return view('quests.show', [
            'quest' => $quest,

            // I posti occupati e la fila, in due elenchi distinti: sono due
            // cose diverse per chi legge, e chi è in attesa deve vedersi in
            // fila e non fra i partecipanti.
            'seatHolders' => $quest->seatHolders()->orderByPivot('joined_at')->get(),
            'waiting' => $quest->waiting()->get(),

            'mioPosto' => $quest->seatOf(request()->user()),
        ]);
    }

    public function book(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('book', $quest);

        $stato = app(BookQuestSeat::class)->handle($quest, $request->user());

        return back()->with('status', $stato === QuestSeatStatus::Waiting
            ? 'I posti erano esauriti: sei in lista d\'attesa. Se qualcuno si ritira, il dungeon master ti chiamerà.'
            : 'Prenotato. Il posto è tuo quando il dungeon master conferma che la serata si fa.');
    }

    public function withdraw(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('withdraw', $quest);

        app(WithdrawFromQuest::class)->handle($quest, $request->user());

        return back()->with('status', 'Ti sei tirato indietro.');
    }

    /** «La serata si fa»: tutti i prenotati diventano confermati insieme. */
    public function confirmNight(Quest $quest): RedirectResponse
    {
        $this->authorize('confirmNight', $quest);

        app(ConfirmQuestNight::class)->handle($quest);

        return back()->with('status', 'Serata confermata: i prenotati hanno ricevuto la notifica.');
    }

    /** Chiamare qualcuno dalla lista d'attesa, quando un posto si libera. */
    public function promote(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorize('promote', $quest);

        $dati = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            app(PromoteFromWaitingList::class)->handle($quest, User::findOrFail($dati['user_id']));
        } catch (QuestUnavailableException $e) {
            // Fra il caricamento della pagina e il clic può essersi liberato
            // o riempito un posto: si dice cosa è cambiato invece di dare 500.
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Chiamato dalla lista d\'attesa.');
    }

    /**
     * Chiudere l'incarico raccontando com'è andata (M9).
     *
     * L'esito scritto è facoltativo perché a volte non c'è niente da dire —
     * una quest abbandonata perché il tavolo si è sciolto non ha una storia —
     * ma è la ragione per cui questa pagina esiste anche dopo la fine.
     */
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
