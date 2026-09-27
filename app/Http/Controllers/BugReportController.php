<?php

namespace App\Http\Controllers;

use App\Actions\Support\FileBugReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Segnala un problema», dal menù.
 *
 * Una pagina e non una finestra dentro la pagina corrente: il modulo serve di
 * rado, e un componente nel menù peserebbe su ogni schermata dell'app per una
 * cosa che si apre una volta al mese. Dopo l'invio si torna da dove si era
 * partiti, così non si perde il posto.
 */
class BugReportController extends Controller
{
    public function create(Request $request): View
    {
        return view('bug-reports.create', [
            // Da dove arrivava: è la pagina che stava guardando quando ha
            // aperto il menù, cioè quella di cui vuole parlare.
            'provenienza' => url()->previous(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'page' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Serve una riga che dica di cosa si tratta.',
            'description.required' => 'Racconta cosa stavi facendo e cosa ti aspettavi.',
            'description.min' => 'Qualche parola in più aiuta a capire il problema.',
        ], [
            'title' => 'titolo',
            'description' => 'descrizione',
        ]);

        app(FileBugReport::class)->handle(
            reporter: $request->user(),
            title: $validated['title'],
            description: $validated['description'],
            page: $validated['page'] ?? null,
            userAgent: $request->userAgent(),
        );

        return redirect($validated['page'] ?? route('home'))
            ->with('status', 'Segnalazione inviata. Ti diremo com\'è andata a finire.');
    }
}
