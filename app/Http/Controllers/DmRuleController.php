<?php

namespace App\Http\Controllers;

use App\Models\DmRule;
use Illuminate\View\View;

class DmRuleController extends Controller
{
    /** Le regole della casa, per sezione nell'ordine deciso dal pannello. */
    public function index(): View
    {
        $this->authorize('viewAny', DmRule::class);

        return view('dm.rules', [
            'gruppi' => DmRule::ordered()->get()->groupBy(fn (DmRule $regola) => $regola->category ?? ''),
        ]);
    }
}
