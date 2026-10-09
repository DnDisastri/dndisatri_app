<?php

namespace App\Http\Controllers;

use App\Enums\NotificationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'characters' => $user->characters()
                // I vivi prima (`died_at` nullo), poi i caduti dal più recente.
                ->orderByRaw('died_at is not null')
                ->orderByDesc('died_at')
                ->orderBy('name')
                ->get(),
            'activeWarning' => $user->activeWarning(),
            'warningHistory' => $user->warningHistory(),
        ]);
    }

    /** Lo storico dei richiami: un richiamo revocato resta in elenco. */
    public function warnings(Request $request): View
    {
        $user = $request->user();

        return view('profile.richiami', [
            'activeWarning' => $user->activeWarning(),
            // Chi l'ha dato e tolto non si carica di proposito: vedi la vista.
            'warnings' => $user->warnings()->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:users,name,'.$user->getKey()],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->getKey()],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [], [
            'name' => 'nome utente',
            'phone' => 'telefono',
        ]);

        $user->update($validated);

        return back()->with('status', 'Profilo aggiornato.');
    }

    /**
     * La password attuale si chiede anche a chi è dentro (sessione lasciata
     * aperta); dopo il cambio la sessione si rigenera.
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

    /** Il modulo manda le categorie attive; si salvano le disattivate (vedi NotificationCategory). */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categorie' => ['array'],
            'categorie.*' => [Rule::enum(NotificationCategory::class)],
        ]);

        $user = $request->user();
        $accese = $validated['categorie'] ?? [];
        $mostrate = collect(NotificationCategory::forUser($user))
            ->map(fn (NotificationCategory $categoria) => $categoria->value);

        // Le categorie non mostrate restano com'erano: un giocatore promosso DM
        // non deve trovarsi «Da approvare» disattivata.
        $spente = $mostrate
            ->reject(fn (string $valore) => in_array($valore, $accese, true))
            ->merge(collect($user->muted_notifications ?? [])->diff($mostrate))
            ->unique()
            ->values()
            ->all();

        $user->forceFill(['muted_notifications' => $spente])->save();

        return back()->with('status', 'Preferenze salvate.');
    }
}
