<?php

namespace Database\Seeders;

use App\Enums\RulesEdition;
use App\Models\Feat;
use App\Models\Species;
use App\Models\Subrace;
use Illuminate\Database\Seeder;

/** Specie, sottospecie e talenti 2024 da `seeders/data/rules_2024.php`. Non riscrive quelli che ci sono già. */
class Rules2024Seeder extends Seeder
{
    public function run(): void
    {
        $dati = require database_path('seeders/data/rules_2024.php');
        $edizione = RulesEdition::Revised->value;

        foreach ($dati['species'] as $posizione => $specie) {
            Species::firstOrCreate(
                ['name' => $specie['name'], 'rules_edition' => $edizione],
                ['asi' => [], 'position' => $posizione, ...$specie],
            );
        }

        foreach ($dati['subraces'] as $specie => $sottospecie) {
            foreach ($sottospecie as $posizione => $voce) {
                Subrace::firstOrCreate(
                    ['race' => $specie, 'name' => $voce['name'], 'rules_edition' => $edizione],
                    ['asi' => [], 'is_homebrew' => false, 'position' => $posizione, ...$voce],
                );
            }
        }

        foreach ($dati['feats'] as $talento) {
            Feat::firstOrCreate(
                ['name' => $talento['name'], 'rules_edition' => $edizione],
                ['is_homebrew' => false, ...$talento],
            );
        }
    }
}
