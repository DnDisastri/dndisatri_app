<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Emporio, annunci e scambi sulla stessa pagina: ogni indirizzo la apre sulla sua sezione. */
class MarketController extends Controller
{
    public const SEZIONI = [
        'market.shop' => 'Emporio',
        'market.listings' => 'Annunci',
        'market.trades' => 'Scambi',
    ];

    public function show(string $sezione): View
    {
        return view('market.index', ['sezione' => $sezione]);
    }
}
