<?php

namespace App\Http\Controllers;

use App\Actions\React;
use App\Enums\Reactable;
use App\Enums\Reaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Le reaction, per tutto quello che le accetta.
 *
 * Una rotta sola e non una per pagina: «ho applaudito questa cosa» è la stessa
 * frase ovunque, e quattro rotte gemelle sarebbero quattro posti dove
 * dimenticarsi il controllo dei permessi.
 */
class ReactionController extends Controller
{
    public function store(Request $request, string $tipo, int $id): RedirectResponse
    {
        // Un tipo fuori elenco non è un permesso negato: è un indirizzo che
        // non esiste, e va trattato come tale.
        $reactable = Reactable::tryFrom($tipo) ?? abort(404);

        $oggetto = $reactable->model()::findOrFail($id);

        /*
         * **Si reagisce solo a quello che si può vedere.** Senza questa riga,
         * una news programmata si applaudirebbe da un indirizzo indovinato —
         * e il conteggio che compare confermerebbe che esiste, che è
         * esattamente quello che P32 evita rispondendo 404.
         */
        abort_unless($request->user()->can('view', $oggetto), 404);

        // Una serata senza resoconto non ha ancora niente da applaudire, e un
        // incarico aperto ha già il suo gesto: «voglio partecipare».
        abort_unless($oggetto->acceptsReactions(), 404);

        $dati = $request->validate([
            'reazione' => ['required', Rule::enum(Reaction::class)],
        ]);

        app(React::class)->handle($oggetto, $request->user(), Reaction::from($dati['reazione']));

        // Si torna dove si stava leggendo, senza messaggio: la faccina che si
        // accende dice già tutto, e un «reaction salvata» in cima alla pagina
        // sarebbe più rumoroso del gesto.
        return back();
    }
}
