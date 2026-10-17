<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

/** Le specie da `seeders/data/species.php`. Non riscrive quelle che ci sono già. */
class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/species.php') as $posizione => $voce) {
            Species::firstOrCreate(['name' => $voce['name']], [...$voce, 'position' => $posizione]);
        }
    }
}
