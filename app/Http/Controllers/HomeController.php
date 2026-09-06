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
    /** La Home: novità, campagne, tavoli, quest e news del gruppo. */
    public function index(): View
    {
        // Gli ospiti vedono la presentazione: due pagine sullo stesso `/`, e la
        // scelta sta qui perché due rotte su `/` non si possono dichiarare
        // (Laravel prende la prima che combacia, le middleware non contano).
        if (! auth()->check()) {
            return view('prelogin', ['illustrazioni' => $this->illustrazioni()]);
        }

        $events = Event::published()->upcoming()->limit(4)->get();

        // Solo le aperte, le più recenti per season, al massimo sei (anteprima).
        $campaigns = Campaign::query()
            ->active()
            ->orderByDesc('season')
            ->orderBy('title')
            ->limit(6)
            ->get();

        // Al plurale: in una sera possono esserci più tavoli.
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
            // Posti liberi in PHP: `freeSlots()` è già la regola, non si duplica in SQL.
            ->filter(fn (Quest $quest) => ! $quest->isFull())
            ->take(4);

        $posts = Post::published()->limit(3)->get();

        return view('home', [
            'events' => $events,
            'campaigns' => $campaigns,
            'sessions' => $sessions,
            'quests' => $quests,
            'posts' => $posts,
            'novita' => $this->novita($events->count(), $sessions->count(), $quests->count()),

            // Banner del tutorial solo a chi non ha ancora un eroe.
            'senzaEroe' => auth()->user()->characters()->doesntExist(),
        ]);
    }

    /**
     * Illustrazioni della presentazione, lette dalla cartella (ordine per nome).
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

    /** Una frase tipo «Due eventi in arrivo e tre incarichi aperti». */
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
