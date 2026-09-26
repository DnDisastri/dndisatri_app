<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuildController extends Controller
{
    /**
     * La Gilda (P13): tutti i personaggi del gruppo, i vivi e in fondo i caduti.
     *
     * I caduti avevano una pagina loro, `/caduti`, ed era un doppione: la stessa
     * domanda — «chi c'è in questa gilda» — a cui si rispondeva in due posti.
     * Adesso la gilda si legge tutta di seguito, e chi non c'è più resta
     * **dentro** il gruppo invece di stare in una stanza a parte.
     *
     * `items` e `itemEffects` servivano a calcolare classe armatura e punti
     * ferita, che dalla card sono spariti: erano due numeri di una serata su
     * una bacheca che si guarda fra una serata e l'altra. Tolti quelli,
     * restano solo le classi — che servono a scrivere un multiclasse per
     * intero invece della sola principale.
     */
    public function index(Request $request): View
    {
        /*
         * Gli extra di chi conduce (M16): la Gilda è la stessa di tutti, ma al
         * DM offre in più una **ricerca** — per nome del personaggio o del
         * giocatore — e il **segno di chi è sotto richiamo**. Al giocatore
         * niente di tutto questo: per lui la Gilda resta la bacheca del gruppo.
         */
        $conduce = $request->user()->isDm();
        $cerca = $conduce ? trim((string) $request->query('cerca', '')) : '';

        $vivi = Character::alive()->with(['user', 'classes'])->orderBy('name');
        $caduti = Character::fallen()->with(['user', 'classes'])->orderByDesc('died_at');

        if ($cerca !== '') {
            // La chiusura raggruppa l'OR, o si mangerebbe la condizione vivo/caduto.
            $filtro = fn ($q) => $q
                ->where('name', 'like', "%{$cerca}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$cerca}%"));

            $vivi->where($filtro);
            $caduti->where($filtro);
        }

        return view('guild.index', [
            'characters' => $vivi->get(),
            'fallen' => $caduti->get(),
            'cerca' => $cerca,

            // Chi è sotto richiamo adesso: gli id dei giocatori, per il segno
            // sulla card. Vuoto per chi non conduce — così la card non lo mostra.
            'sottoRichiamo' => $conduce
                ? Warning::active()->pluck('user_id')->unique()->all()
                : [],
        ]);
    }

    /**
     * Il caduto (P15b): la pagina che rende la Hall of Fallen Heroes una cosa
     * e non un elenco.
     *
     * Come è morto era **già raccontato e già salvato** — `death_story` e la
     * serata li scrive chi conduce quando chiude una scheda — e non si leggeva
     * da nessuna parte. Una colonna che raccoglieva racconti per nessuno.
     *
     * Su un personaggio vivo è un 404 e non una pagina vuota: il memoriale di
     * chi non è morto non esiste, e un indirizzo scritto a mano non deve
     * trovare una lapide col nome di qualcuno che sta benissimo.
     */
    public function fallenShow(Character $character): View
    {
        abort_if($character->isAlive(), 404);

        // La campagna della serata serve a scrivere «Sessione 12 — I Tre Regni»:
        // il numero da solo non dice a quale storia apparteneva.
        $character->load(['user', 'classes', 'diedInSession.campaign']);

        return view('guild.caduto', ['character' => $character]);
    }
}
