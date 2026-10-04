<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Approvals\AnnounceForApproval;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationAwaitingApproval;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Il nome è univoco: nella Gilda i giocatori si riconoscono da lì.
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:users,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'discovery_source' => ['nullable', 'string', 'max:500'],
            'played_before' => ['required', 'boolean'],
        ], [
            'played_before.required' => 'Dicci se hai già fatto sessioni con noi.',
        ], [
            'name' => 'nome utente',
        ]);

        // Nasce in attesa e non entra finché un admin non lo approva.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'discovery_source' => filled($validated['discovery_source'] ?? null) ? $validated['discovery_source'] : null,
            'played_before' => (bool) $validated['played_before'],
        ]);

        // Gli altri ruoli si assegnano solo da codice server, mai da un modulo.
        $user->assignRole(Role::findOrCreate(User::ROLE_PLAYER, 'web'));

        event(new Registered($user));

        app(AnnounceForApproval::class)->handle(
            new RegistrationAwaitingApproval($user),
            ruoli: [User::ROLE_ADMIN],
        );

        return redirect()->route('login')->with('status',
            'Registrazione ricevuta. Un amministratore deve approvare l\'account prima del primo accesso.');
    }
}
