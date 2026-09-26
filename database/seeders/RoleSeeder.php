<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * I tre ruoli del gruppo. Non sono dati modificabili dall'applicazione: il
 * cambio di ruolo passa sempre e solo da codice server (§7.3 del brief), mai
 * da una richiesta del client come succedeva prima.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_DM, User::ROLE_PLAYER] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
