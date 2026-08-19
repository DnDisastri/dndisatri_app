<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea un account di amministrazione, o promuove un account esistente.
 *
 * I ruoli si assegnano solo da qui, cioè da codice server: è precisamente ciò
 * che mancava nella vecchia applicazione, dove un utente poteva scriversi
 * `role: 'dm'` dalla console del browser (§8.1 del brief).
 *
 * Le credenziali non stanno in nessun file: le digita chi lancia il comando.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'dndisastri:admin';

    protected $description = 'Crea un account admin, o promuove ad admin un account esistente';

    public function handle(): int
    {
        $email = text(
            label: 'Email',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL)
                ? null
                : 'Indirizzo email non valido.',
        );

        $existing = User::where('email', $email)->first();

        return $existing
            ? $this->promote($existing)
            : $this->createNew($email);
    }

    private function promote(User $user): int
    {
        if ($user->isAdmin()) {
            $this->components->warn("{$user->name} è già un amministratore.");

            return self::SUCCESS;
        }

        if (! confirm("L'account «{$user->name}» esiste già. Vuoi promuoverlo ad admin?")) {
            return self::FAILURE;
        }

        if ($user->characters()->exists()) {
            $this->components->warn(
                'Attenzione: questo account ha dei personaggi, mentre gli admin non giocano. '.
                'I personaggi restano, ma non potrà crearne di nuovi.'
            );
        }

        $user->assignRole(Role::findOrCreate(User::ROLE_ADMIN, 'web'));

        $this->components->info("{$user->name} è ora un amministratore.");

        return self::SUCCESS;
    }

    private function createNew(string $email): int
    {
        $name = text(
            label: 'Nome',
            required: true,
            validate: fn (string $value) => User::where('name', $value)->exists()
                ? 'Esiste già un utente con questo nome.'
                : null,
        );

        $secret = password(
            label: 'Password',
            required: true,
            validate: function (string $value) {
                $validator = validator(
                    ['password' => $value],
                    ['password' => ['required', Password::min(8)]],
                );

                return $validator->fails()
                    ? $validator->errors()->first('password')
                    : null;
            },
        );

        if (password(label: 'Ripeti la password', required: true) !== $secret) {
            $this->components->error('Le due password non coincidono.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
        ]);

        // Verificato e **approvato** subito: un admin creato da riga di comando
        // è per definizione ammesso, e senza approvazione non potrebbe entrare.
        $user->forceFill(['email_verified_at' => now(), 'approved_at' => now()])->save();
        $user->assignRole(Role::findOrCreate(User::ROLE_ADMIN, 'web'));

        $this->components->info("Amministratore «{$name}» creato.");
        $this->components->bulletList([
            'Accede al pannello su /admin',
            'Non ha personaggi e non compare nella Gilda',
            'Non può approvare richieste di un proprio personaggio',
        ]);

        return self::SUCCESS;
    }
}
