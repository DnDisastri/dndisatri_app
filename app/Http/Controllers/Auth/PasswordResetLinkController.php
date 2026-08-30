<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Invio del link per reimpostare la password.
 *
 * Servirà anche alla migrazione dei dati: gli hash delle password di Firebase
 * non si riusano, quindi al passaggio si creano gli account e si manda a tutti
 * un link da qui (§10 del brief).
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Si risponde sempre allo stesso modo, anche se l'indirizzo non
        // esiste: altrimenti si potrebbe scoprire chi fa parte del gruppo
        // provando indirizzi a caso.
        return back()->with('status', __('Se l\'indirizzo è registrato, riceverai un messaggio con le istruzioni.'));
    }
}
