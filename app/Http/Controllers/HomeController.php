<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\Post;
use App\Models\Quest;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * La Home (P4): cosa succede nel gruppo.
     *
     * **Il personaggio non sta qui.** Ha la sua sezione nella barra in basso:
     * questa pagina racconta la gilda, il personaggio è affare di chi lo
     * gioca. È il motivo per cui la vecchia bacheca è stata svuotata.
     *
     * Sei blocchi, nell'ordine in cui uno se li chiede: cosa c'è di nuovo,
     * cosa si festeggia, **a quali storie si può partecipare**, quando si
     * gioca, cosa si può fare, cosa è successo.
     */
    public function index(): View
    {
        /*
         * Chi non ha fatto l'accesso vede la **presentazione** (P0), non la
         * Home. È l'unico indirizzo dell'applicazione che serve due pagine
         * diverse, e la scelta sta qui e non nelle rotte: due rotte sullo
         * stesso `/`, una per gli ospiti e una per gli altri, non si possono
         * dichiarare — Laravel prende la prima che combacia e le middleware
         * non entrano nella scelta.
         *
         * La Home è di tutti, DM compresi: chi conduce ci atterra come gli
         * altri, e alla Regia arriva dallo scudo nella barra. Nessuno viene
         * dirottato all'ingresso — sarebbe una porta che si chiude in faccia a
         * chi è anche un giocatore.
         */
        if (! auth()->check()) {
            return view('prelogin', ['illustrazioni' => $this->illustrazioni()]);
        }

        $events = Event::published()->upcoming()->limit(4)->get();

        /*
         * Solo le campagne **aperte**: la Home racconta cosa sta succedendo, e
         * una storia finita non è una porta in cui entrare. Le concluse
         * restano nell'elenco (P16) e nel Libro Mastro, che è il posto della
         * memoria.
         *
         * Le più recenti per season: chi apre la Home cerca il tavolo di
         * adesso, non quello di due stagioni fa che non si è mai chiuso.
         *
         * **Sei al massimo**, che in griglia a due colonne sono tre righe
         * piene. Oltre, la Home smetterebbe di essere un assaggio e
         * diventerebbe l'elenco delle campagne, che è un'altra pagina (P16) —
         * e il pulsante qui sotto ci porta.
         */
        $campaigns = Campaign::query()
            ->active()
            ->orderByDesc('season')
            ->orderBy('title')
            ->limit(6)
            ->get();

        // Al plurale: in una stessa sera possono girare due tavoli diversi, e
        // mostrarne uno solo darebbe l'idea sbagliata di cosa succede.
        $sessions = GameSession::query()
            ->upcoming()
            ->with('campaign')
            ->limit(4)
            ->get();

        $quests = Quest::query()
            ->active()
            ->withCount('participants')
            ->with('campaign')
            ->latest('id')
            ->get()
            // I posti liberi si contano in PHP e non in SQL: `freeSlots()` è
            // già la regola, e riscriverla in una query la farebbe esistere
            // in due posti che possono divergere.
            ->filter(fn (Quest $quest) => ! $quest->isFull())
            ->take(4);

        $posts = Post::published()->limit(3)->get();

        return view('home', [
            'events' => $events,
            'campaigns' => $campaigns,
            'sessions' => $sessions,
            'quests' => $quests,
            'posts' => $posts,

            // La riga sotto il benvenuto: dice cosa c'è di nuovo, e se non c'è
            // niente lo dice lo stesso invece di sparire.
            'novita' => $this->novita($events->count(), $sessions->count(), $quests->count()),
        ]);
    }

    /**
     * Le illustrazioni della presentazione.
     *
     * Si leggono dalla cartella invece di essere elencate nel codice: cambiare
     * le figure di benvenuto è una cosa da fare copiando dei file, non
     * modificando una vista. Aggiungerne una quarta vuol dire metterla lì.
     *
     * L'ordine è quello del nome, quindi si decide chiamandole `1-…`, `2-…`.
     *
     * @return list<string>
     */
    private function illustrazioni(): array
    {
        $cartella = public_path('images/prelogin');

        if (! is_dir($cartella)) {
            return [];
        }

        $ammessi = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'avif'];

        $file = collect(scandir($cartella) ?: [])
            ->filter(fn (string $nome) => in_array(
                strtolower(pathinfo($nome, PATHINFO_EXTENSION)),
                $ammessi,
                true,
            ))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $file;
    }

    /**
     * «Due eventi in arrivo e tre incarichi aperti.»
     *
     * Un elenco di frasi unite bene vale più di tre contatori in fila: si
     * legge in un colpo, che è tutto quello che deve fare una riga sotto il
     * benvenuto.
     */
    private function novita(int $eventi, int $tavoli, int $incarichi): string
    {
        $pezzi = [];

        if ($eventi > 0) {
            $pezzi[] = $eventi === 1 ? 'un evento in arrivo' : "{$eventi} eventi in arrivo";
        }

        if ($tavoli > 0) {
            $pezzi[] = $tavoli === 1 ? 'un tavolo in programma' : "{$tavoli} tavoli in programma";
        }

        if ($incarichi > 0) {
            $pezzi[] = $incarichi === 1 ? 'una quest aperta' : "{$incarichi} quest aperte";
        }

        if ($pezzi === []) {
            return 'Per adesso è tutto tranquillo.';
        }

        $ultimo = array_pop($pezzi);

        $frase = $pezzi === [] ? $ultimo : implode(', ', $pezzi).' e '.$ultimo;

        return ucfirst($frase).'.';
    }
}
