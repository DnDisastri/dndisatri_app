<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNewlines;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionJournalController extends Controller
{
    use NormalizesNewlines;

    /** Scrive, corregge o (vuota) toglie la nota del giocatore su una sessione giocata. */
    public function update(Request $request, Character $character, GameSession $session): RedirectResponse
    {
        $this->authorize('writeJournal', $character);
        abort_unless($character->sessions()->whereKey($session->getKey())->exists(), 404);

        $this->normalizeNewlines($request, 'body');

        $dati = $request->validateWithBag('diario-'.$session->getKey(), [
            'body' => ['nullable', 'string', 'max:'.SessionJournal::MAX],
        ], [
            'body.max' => 'Al massimo :max caratteri.',
        ]);

        // Dal Registro o dalla pagina della sessione: si torna dov'era il modale.
        $ancora = $request->input('ancora') === 'diario' ? 'diario' : 'sessioni';
        $chiave = ['character_id' => $character->getKey(), 'game_session_id' => $session->getKey()];

        if (blank($dati['body'] ?? null)) {
            SessionJournal::where($chiave)->delete();

            return redirect()->to(url()->previous().'#'.$ancora)->with('status', 'Nota tolta.');
        }

        SessionJournal::updateOrCreate($chiave, ['body' => $dati['body']]);

        return redirect()->to(url()->previous().'#'.$ancora)->with('status', 'Nota salvata.');
    }
}
