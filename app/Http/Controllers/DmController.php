<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La regia: l'app vista da chi conduce.
 *
 * **Non è la stessa app del giocatore con un tasto in più.** Un giocatore apre
 * l'applicazione per il suo personaggio; un DM per la sua serata e il suo
 * tavolo. Sono due mestieri, e questa è la porta del secondo.
 *
 * Le decisioni pesanti — approvare, giudicare, ammonire — restano nel Pannello
 * (D20): qui si **conduce**, di là si **giudica**. Quello che vive qui sono i
 * gesti da tavolo, col telefono in mano e la luce bassa.
 *
 * La priorità è sulle **proprie** campagne, ma nessun DM ci è chiuso dentro:
 * stasera può toccare a te coprire un collega che sta male, e allora la sua
 * campagna deve essere raggiungibile. È la stessa logica dei permessi sui
 * personaggi (decisione D1): il ruolo, non il tavolo.
 */
class DmController extends Controller
{
    public function home(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isDm(), 403);

        // Le mie, in cima. Le altre attive restano a portata per l'emergenza.
        $mie = Campaign::query()->active()->runBy($user)
            ->orderByDesc('season')->orderBy('title')->get();

        $altre = Campaign::query()->active()
            ->where('dm_id', '!=', $user->getKey())
            ->orderByDesc('season')->orderBy('title')->get();

        $corrente = $this->campagnaAFuoco($request, $mie, $altre);

        return view('dm.home', [
            'mie' => $mie,
            'altre' => $altre,
            'corrente' => $corrente,
            'serata' => $corrente ? $this->serataInMano($corrente) : null,
            'tavolo' => $corrente ? $corrente->roster() : collect(),
            // Sto conducendo la campagna di un altro? La home lo dice, senza
            // impedirlo: è l'emergenza, non l'abitudine.
            'sostituto' => $corrente !== null && $corrente->dm_id !== $user->getKey(),
        ]);
    }

    public function prepare(Request $request, GameSession $session): View
    {
        abort_unless($request->user()->isDm(), 403);

        $session->load('campaign');

        return view('dm.prepare', [
            'session' => $session,
            'campagna' => $session->campaign,
        ]);
    }

    /**
     * La campagna a fuoco: quella scelta nell'indirizzo (`?campagna=slug`),
     * poi la prima delle mie, e solo se non conduco niente ricado su un'altra.
     */
    private function campagnaAFuoco(Request $request, $mie, $altre): ?Campaign
    {
        $slug = $request->string('campagna')->toString();

        if ($slug !== '') {
            $scelta = $mie->firstWhere('slug', $slug) ?? $altre->firstWhere('slug', $slug);

            if ($scelta !== null) {
                return $scelta;
            }
        }

        return $mie->first() ?? $altre->first();
    }

    /**
     * La serata che il DM ha «in mano» adesso: la prossima da giocare se c'è,
     * altrimenti l'ultima giocata. È quella su cui si apre la regia.
     */
    private function serataInMano(Campaign $campagna): ?GameSession
    {
        return $campagna->sessions()->upcoming()->first()
            ?? $campagna->sessions()->past()->first();
    }
}
