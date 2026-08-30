<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * La serata (P21).
 *
 * **Il resoconto è il contenuto della pagina, non una nota a piè di pagina.**
 * Fra un anno di una serata non si ricorda la data: si ricorda cosa è
 * successo, e questo è il posto dove sta scritto.
 *
 * I due gesti di chi conduce vivono qui e non nel Pannello (D20): il recap si
 * scrive **dove i giocatori lo leggeranno**, e le presenze si spuntano quando
 * si spegne la luce, col telefono in mano.
 */
class GameSessionController extends Controller
{
    /**
     * Il calendario delle serate (P20).
     *
     * Un **calendario** e non un elenco: una serata si guarda per sapere
     * quando è, e un mese si legge a colpo d'occhio mentre un elenco si legge
     * riga per riga. È la stessa ragione per cui i calendari esistono.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', GameSession::class);

        /*
         * Il mese arriva dall'indirizzo, così una pagina si può mandare a
         * qualcuno. Un mese scritto storto non è un errore — è un indirizzo
         * vecchio o una prova — quindi si ricade su quello corrente invece di
         * rispondere 404.
         */
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

            // Le serate del mese raccolte per giorno: il calendario deve
            // sapere quali caselle segnare, e in una stessa sera possono
            // girare due tavoli diversi.
            'perGiorno' => $sessions->groupBy(fn (GameSession $s) => $s->played_at->toDateString()),

            /*
             * Gli eventi in fondo. Sono l'altra metà del «quando si gioca»: le
             * serate appartengono a una storia, i raduni e le one-shot no, e
             * chi apre il calendario le sta cercando tutte e due. Non seguono
             * il mese scelto perché sono i **prossimi** — un raduno lo si
             * guarda per prenotarsi, non per ricostruire marzo.
             */
            'events' => Event::published()->upcoming()->limit(4)->get(),
        ]);
    }

    public function show(GameSession $session): View
    {
        $this->authorize('view', $session);

        $session->load(['campaign.dm', 'recapWrittenBy', 'attendees.characters']);

        // La serata prima e quella dopo, **nella stessa campagna**: si legge una
        // storia in fila, e da una serata il passo più naturale è alla vicina.
        // L'ordine è il tempo, non il numero: una serata recuperata fuori
        // sequenza sta dove è stata giocata.
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

            /*
             * Il tavolo a colpo d'occhio, solo per chi conduce (M16 lato serata):
             * i personaggi che hanno giocato questa campagna, coi numeri della
             * serata — punti ferita, oro, stato. È la cosa che al DM serve
             * mentre gioca e che la pagina del giocatore, giustamente, nasconde.
             *
             * A qualsiasi DM, non solo a quello del tavolo: al colpo d'occhio ci
             * si arriva anche coprendo un collega. Ai giocatori niente.
             */
            'tavolo' => auth()->user()?->isDm()
                ? $session->campaign->roster()
                : collect(),

            /*
             * Chi si può spuntare come presente. Gli admin restano fuori: non
             * hanno personaggi e non giocano, e comparirebbero in fondo a ogni
             * elenco senza che nessuno li spunti mai.
             *
             * Si carica solo a chi deve segnare le presenze — a un giocatore
             * questo elenco non serve, ed è una query e mezza in meno su una
             * pagina che leggono tutti.
             */
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

        return back()->with('status', 'Resoconto salvato.');
    }

    /**
     * Le presenze (M14).
     *
     * Arrivano come `presenti[]` — gli id spuntati — più `personaggi[id]` con
     * la scelta della tendina. Si tengono solo i personaggi di chi è davvero
     * spuntato: la tendina resta compilata anche quando la casella si
     * ridisattiva, e senza questo filtro si segnerebbe il personaggio di un
     * assente.
     */
    public function recordAttendance(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('recordAttendance', $session);

        $dati = $request->validate([
            'presenti' => ['array'],
            'presenti.*' => ['integer', Rule::exists('users', 'id')],
            'personaggi' => ['array'],
            'personaggi.*' => ['nullable', 'integer', Rule::exists('characters', 'id')],
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

        return back()->with('status', 'Presenze salvate.');
    }
}
