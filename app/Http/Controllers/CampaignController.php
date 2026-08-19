<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * L'elenco delle campagne (P16).
     *
     * Prima le attive, poi le concluse: chi apre questa pagina quasi sempre
     * cerca il tavolo di stasera, non quello di due anni fa.
     *
     * Il filtro per season è già qui con tre campagne perché il problema
     * arriva tutto insieme: alla sesta season la pagina diventa illeggibile e
     * a quel punto il filtro va aggiunto su una vista già piena.
     */
    public function index(Request $request): View
    {
        $seasons = Campaign::seasons();

        // Una season chiesta e inesistente non è un errore: è un indirizzo
        // vecchio, o una prova. Si ricade su tutte invece di dare 404.
        $season = $request->integer('season') ?: null;

        if ($season !== null && ! in_array($season, $seasons, true)) {
            $season = null;
        }

        $campaigns = Campaign::query()
            ->when($season !== null, fn ($query) => $query->inSeason($season))
            ->with('dm')
            // `ended_at` nullo vuol dire attiva, e in SQL il nullo non si
            // ordina da solo: la colonna calcolata lo rende esplicito.
            ->orderByRaw('CASE WHEN ended_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('season')
            ->orderBy('title')
            ->get();

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'seasons' => $seasons,
            'season' => $season,
        ]);
    }

    /**
     * Il dettaglio (P17).
     *
     * L'ordine della pagina risponde alle domande nell'ordine in cui uno se le
     * fa: «cosa mi sono perso?» prima di «di cosa parla?», e «quando si gioca?»
     * prima dell'archivio.
     */
    public function show(Campaign $campaign): View
    {
        $campaign->load('dm');

        return view('campaigns.show', [
            'campaign' => $campaign,

            // L'ultima giocata e la prossima: due domande diverse, due query.
            'lastSession' => $campaign->sessions()->past()->first(),
            'nextSession' => $campaign->sessions()->upcoming()->first(),

            /*
             * **Solo le aperte.** Le concluse stavano qui in fondo, spente, e
             * su un tavolo con una season alle spalle erano otto righe su
             * dodici: la sezione diceva soprattutto quello che *non* si può
             * più fare. Il loro posto è il Libro Mastro, che le mostra già
             * filtrate per campagna — tenerle in due posti voleva dire due
             * elenchi da allineare per raccontare la stessa cosa.
             */
            'quests' => $campaign->quests()->active()->latest('id')->get(),

            // Quante ce ne sono nell'archivio: il collegamento al Libro Mastro
            // lo dice, e un «vedi l'archivio» che porta a zero righe è un
            // invito sprecato.
            'questsConcluse' => $campaign->quests()->archived()->count(),

            'sessions' => $campaign->sessions()->past()->get(),

            'maps' => Map::forCampaign($campaign)->orderBy('title')->get(),

            // Chi ha giocato a questo tavolo, senza ripetizioni: si ricava
            // dalle presenze, non da un elenco tenuto a mano che andrebbe
            // aggiornato ogni volta che qualcuno si siede.
            'characters' => Character::query()
                ->whereHas('sessions', fn ($query) => $query->where('campaign_id', $campaign->getKey()))
                ->with('user')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
