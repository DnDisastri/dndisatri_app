<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * Le news della gilda (P31).
     *
     * **In evidenza prima, poi le più recenti** — è già l'ordine dello scope
     * `published()`, così la Home e questa pagina non possono raccontare due
     * gerarchie diverse.
     *
     * Si vede **solo il pubblicato**: una data futura in `published_at` è una
     * pubblicazione programmata, e mostrarla in anticipo vanificherebbe il
     * motivo per cui è stata programmata.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Post::class);

        return view('news.index', [
            'posts' => Post::published()->with('author')->get(),
        ]);
    }

    /**
     * Il dettaglio (P32): copertina, titolo, chi l'ha scritta, quando, il testo.
     *
     * Una news non ancora pubblicata **non esiste** per chi guarda — 404 e non
     * «non hai il permesso»: dire che c'è qualcosa di nascosto è già dire
     * qualcosa di un annuncio che era programmato apposta. È la stessa regola
     * degli eventi (P34).
     *
     * Un admin invece la vede, ed è così che si rilegge una bozza prima di
     * pubblicarla senza doverla pubblicare per vederla.
     */
    public function show(Post $post): View
    {
        abort_unless(auth()->user()->can('view', $post), 404);

        $post->load('author');

        return view('news.show', ['post' => $post]);
    }
}
