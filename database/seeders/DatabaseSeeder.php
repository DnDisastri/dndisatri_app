<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Gli account admin si creano con `php artisan dndisastri:admin`, non qui. */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            // Il catalogo delle regole, da cui la creazione del personaggio
            // legge: senza, nessuno può scegliere una sottoclasse.
            SubclassSeeder::class,
            SubraceSeeder::class,
            // Contenuti di partenza modificabili dal pannello, anche in produzione.
            MarketSeeder::class,
            FaqSeeder::class,
            TutorialSeeder::class,
            AboutSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                DevUserSeeder::class,
                DevCharacterSeeder::class,
            ]);
        }
    }
}
