<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Il mio profilo (P37).
 *
 * È la pagina della **persona**, non del personaggio: nome, email, password.
 * Quello che si può fare con un eroe sta ne «I miei eroi» (P42), e qui i
 * personaggi compaiono solo come elenco con il collegamento alla scheda.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'characters' => $user->characters()
                // I vivi prima: `died_at` è nullo per loro, e in coda ci vanno
                // i caduti dal più recente.
                ->orderByRaw('died_at is not null')
                ->orderByDesc('died_at')
                ->orderBy('name')
                ->get(),
            'activeWarning' => $user->activeWarning(),
            'warningHistory' => $user->warningHistory(),
        ]);
    }

    /**
     * I miei richiami (P38): lo storico completo.
     *
     * Nel profilo (P37) c'è solo il riepilogo di due numeri; qui c'è la lista —
     * quando, chi l'ha dato, il motivo, quanto è durato, chi l'ha tolto. E **non
     * si cancella** quando un richiamo viene tolto: è il punto del meccanismo,
     * serve a ricordare e non a punire per sempre.
     *
     * La pagina esiste per chiunque sia loggato: chi non ha richiami la apre e
     * legge che non ne ha — meglio di un 404 su un indirizzo che il profilo
     * potrebbe aver linkato.
     */
    public function warnings(Request $request): View
    {
        $user = $request->user();

        return view('profile.richiami', [
            'activeWarning' => $user->activeWarning(),
            // I più recenti in cima: l'ultimo preso è quello che interessa di
            // più, e se ce n'è uno attivo è quasi sempre lì. **Chi** l'ha dato e
            // tolto non si carica di proposito — vedi la nota nella vista.
            'warnings' => $user->warnings()->latest()->get(),
        ]);
    }

    /**
     * Nome ed email.
     *
     * Il nome è univoco — nella Gilda i giocatori si riconoscono da lì — e
     * `ignore` toglie di mezzo il caso in cui uno salva senza aver cambiato
     * niente, che altrimenti si scontrerebbe con sé stesso.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:users,name,'.$user->getKey()],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->getKey()],
        ], [], [
            'name' => 'nome utente',
        ]);

        $user->update($validated);

        return back()->with('status', 'Profilo aggiornato.');
    }

    /**
     * La password.
     *
     * Si chiede quella attuale anche a chi è già dentro: una sessione lasciata
     * aperta su un telefono in giro non deve bastare a cambiare le chiavi di
     * casa.
     *
     * Dopo il cambio si rigenera l'identificativo di sessione: è la difesa
     * contro chi se ne fosse impossessato prima.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'current_password' => 'password attuale',
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        $request->session()->regenerate();

        return back()->with('status', 'Password cambiata.');
    }
}
