<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Utenti finti per lo sviluppo: due DM e due giocatori, niente personaggi né
 * campagne. Bastano a entrare nel pannello e a provare i permessi.
 *
 * Gli admin NON stanno qui: si creano con `php artisan dndisastri:admin`, che
 * chiede le credenziali a schermo e non le scrive in nessun file.
 *
 * Password comune: `password`.
 */
class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DevUserSeeder crea utenti con una password nota: non va eseguito in produzione.'
            );
        }

        $this->call(RoleSeeder::class);

        foreach ([
            ['Aurelio il Narratore', 'dm1@dndisastri.test', User::ROLE_DM],
            ['Morgana la Custode', 'dm2@dndisastri.test', User::ROLE_DM],
            ['Bruno', 'giocatore1@dndisastri.test', User::ROLE_PLAYER],
            ['Delia', 'giocatore2@dndisastri.test', User::ROLE_PLAYER],
        ] as [$name, $email, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password'],
            );

            // Verificati e approvati: sono account di prova, devono poter entrare.
            $user->forceFill(['email_verified_at' => now(), 'approved_at' => now()])->save();
            $user->syncRoles([$role]);
        }

        $this->command?->info('Utenti di sviluppo pronti. Password: password');
    }
}
