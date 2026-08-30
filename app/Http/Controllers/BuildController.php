<?php

namespace App\Http\Controllers;

use App\Models\Build;
use Illuminate\View\View;

class BuildController extends Controller
{
    /**
     * Le build consigliate (P43): personaggi di 1° già pensati, per chi vuole
     * partire senza studiarsi il manuale.
     *
     * Si vede **solo il pubblicato**: le bozze restano a chi le sta scrivendo,
     * nel Pannello. L'elenco cresce perché da adesso le scrivono i DM.
     */
    public function index(): View
    {
        return view('builds.index', [
            'builds' => Build::published()->get(),
        ]);
    }

    /**
     * Il dettaglio (P44): com'è fatta e perché funziona, e in fondo il pulsante
     * per usarla nella creazione.
     *
     * Una build non pubblicata non esiste per chi guarda — 404, non «non hai il
     * permesso»: è una bozza di qualcun altro, e non c'è niente da rivelare.
     */
    public function show(Build $build): View
    {
        abort_unless($build->isPublished(), 404);

        return view('builds.show', ['build' => $build]);
    }
}
