<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    /**
     * Il Libro Mastro (P30): la memoria del gruppo.
     *
     * Le quest concluse, le serate giocate coi resoconti, i personaggi caduti.
     * È l'archivio **condiviso**, non l'estratto conto di un personaggio —
     * quello è il suo registro, ed è un'altra pagina.
     *
     * Il filtro è per campagna, e la season lo restringe a monte: sono due
     * livelli dello stesso taglio, non due filtri indipendenti, perché una
     * campagna appartiene a una season sola.
     */
    public function index(Request $request): View
    {
        $seasons = Campaign::seasons();

        $season = $request->integer('season') ?: null;

        if ($season !== null && ! in_array($season, $seasons, true)) {
            $season = null;
        }

        $campaigns = Campaign::query()
            ->when($season !== null, fn ($query) => $query->inSeason($season))
            ->orderByDesc('season')
            ->orderBy('title')
            ->get();

        // La campagna scelta deve essere fra quelle che il filtro season
        // lascia passare, o i due filtri si contraddirebbero a schermo.
        $campaign = null;

        if ($request->filled('campagna')) {
            $campaign = $campaigns->firstWhere('slug', $request->string('campagna')->toString());
        }

        $ids = $campaign ? [$campaign->getKey()] : $campaigns->modelKeys();

        return view('ledger.index', [
            'seasons' => $seasons,
            'season' => $season,
            'campaigns' => $campaigns,
            'campaign' => $campaign,

            'quests' => Quest::query()
                ->whereIn('campaign_id', $ids)
                ->archived()
                ->with('campaign')
                ->orderByRaw('COALESCE(completed_at, closed_at) DESC')
                ->get(),

            'sessions' => GameSession::query()
                ->whereIn('campaign_id', $ids)
                ->past()
                ->withRecap()
                ->with('campaign')
                ->get(),

            // I caduti non stanno più qui: il loro posto è la Gilda (P13, P15b).
            // Tenerli anche nel Libro Mastro era un doppione.
        ]);
    }
}
