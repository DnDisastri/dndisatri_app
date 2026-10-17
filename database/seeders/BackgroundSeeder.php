<?php

namespace Database\Seeders;

use App\Models\Background;
use Illuminate\Database\Seeder;

/** I background da `seeders/data/backgrounds.php`, 2014 e 2024. Non riscrive quelli che ci sono già. */
class BackgroundSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/backgrounds.php') as $posizione => $voce) {
            Background::firstOrCreate(
                ['name' => $voce['name'], 'rules_edition' => $voce['rules_edition']],
                [...$voce, 'position' => $posizione],
            );
        }
    }
}
