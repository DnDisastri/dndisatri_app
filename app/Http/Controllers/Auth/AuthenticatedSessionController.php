<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Le credenziali erano giuste, ma l'account è ancora in attesa: lo si
        // fa uscire subito e si spiega perché. Il controllo sta **dopo**
        // l'autenticazione, non prima, o rivelerebbe quali email esistono.
        if (! $request->user()->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Questo account è ancora in attesa di approvazione da parte di un amministratore.',
            ]);
        }

        // Rigenerare la sessione impedisce il session fixation: chi conoscesse
        // l'identificativo di sessione precedente non se ne fa più niente.
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
