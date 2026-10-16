<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\TutorialStep;
use Illuminate\View\View;

class FaqController extends Controller
{
    /**
     * `groupBy` conserva l'ordine di prima comparsa e la query è già ordinata
     * per posizione: le sezioni escono nell'ordine deciso dal pannello.
     */
    public function index(): View
    {
        $gruppi = Faq::published()->for(auth()->check())->ordered()->get()
            ->groupBy(fn (Faq $faq) => $faq->category ?? '');

        return view('faq.index', [
            'gruppi' => $gruppi,
            'passi' => auth()->check() ? TutorialStep::published()->ordered()->get() : collect(),
        ]);
    }
}
