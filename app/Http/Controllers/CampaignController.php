<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $seasons = Campaign::seasons();

        // Una season inesistente ricade su tutte invece di dare 404.
        $season = $request->integer('season') ?: null;

        if ($season !== null && ! in_array($season, $seasons, true)) {
            $season = null;
        }

        $campaigns = Campaign::query()
            ->when($season !== null, fn ($query) => $query->inSeason($season))
            ->with('dm')
            // `ended_at` nullo = attiva: in SQL il nullo non si ordina da solo.
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

    public function show(Campaign $campaign): View
    {
        $campaign->load('dm');

        return view('campaigns.show', [
            'campaign' => $campaign,

            'lastSession' => $campaign->sessions()->past()->first(),
            'nextSession' => $campaign->sessions()->upcoming()->first(),

            // Solo le aperte: le concluse stanno nel Libro Mastro.
            'quests' => $campaign->quests()->active()->latest('id')->get(),

            // Il link al Libro Mastro compare solo se non porta a zero righe.
            'questsConcluse' => $campaign->quests()->archived()->count(),

            // Solo le ultime: l'archivio completo è il Libro Mastro.
            'sessions' => $campaign->sessions()->past()->limit(6)->get(),
            'serateGiocate' => $campaign->sessions()->past()->count(),

            'maps' => Map::forCampaign($campaign)->orderBy('title')->get(),

            // Si ricava dalle presenze alle serate, senza ripetizioni.
            'characters' => Character::query()
                ->whereHas('sessions', fn ($query) => $query->where('campaign_id', $campaign->getKey()))
                ->with('user')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
