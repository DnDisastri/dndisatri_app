<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        ], [], [
            'name' => 'nome utente',
        ]);

        // Nasce **in attesa**: `approved_at` resta nullo finché un amministratore
        // non lo approva. È la porta in più contro chi si registra senza essere
        // del gruppo — e per questo **non si fa entrare** dopo la registrazione.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Chi si registra è un giocatore. Gli altri ruoli si assegnano solo da
        // codice server: `dndisastri:admin` per gli admin, l'approvazione di
        // una richiesta per i DM. Mai da un form.
        $user->assignRole(Role::findOrCreate(User::ROLE_PLAYER, 'web'));

        event(new Registered($user));

        return redirect()->route('login')->with('status',
            'Registrazione ricevuta. Un amministratore deve approvare l\'account prima del primo accesso.');
    }
}
