<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * In produzione gira solo il seeder dei ruoli: gli account admin si creano
     * con `php artisan dndisastri:admin`, che chiede le credenziali a schermo.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            // Il catalogo del negozio serve anche in produzione: è il punto di
            // partenza che gli admin poi modificano.
            MarketSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                DevUserSeeder::class,
                DevCharacterSeeder::class,
            ]);
        }
    }
}
