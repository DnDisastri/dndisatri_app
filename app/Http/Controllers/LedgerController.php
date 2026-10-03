<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    /** La season restringe le campagne a monte: una campagna appartiene a una season sola. */
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

        // La campagna deve stare fra quelle della season scelta, o i filtri si contraddicono.
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

            // Pagine indipendenti, ognuna col suo parametro; i filtri restano nell'indirizzo.
            'quests' => Quest::query()
                ->whereIn('campaign_id', $ids)
                ->archived()
                ->with('campaign')
                ->orderByRaw('COALESCE(completed_at, closed_at) DESC')
                ->simplePaginate(10, pageName: 'quest')
                ->withQueryString(),

            'sessions' => GameSession::query()
                ->whereIn('campaign_id', $ids)
                ->past()
                ->withRecap()
                ->with('campaign')
                ->simplePaginate(6, pageName: 'serate')
                ->withQueryString(),        ]);
    }
}
