<?php

namespace Database\Seeders;

use App\Models\GameClass;
use App\Models\Subclass;
use Illuminate\Database\Seeder;

/**
 * Le classi da `seeders/data/classes.php`. Non riscrive quelle che ci sono già.
 * L'Artefice porta con sé le sottoclassi: le altre arrivano da `SubclassSeeder`.
 */
class GameClassSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/classes.php') as $posizione => $voce) {
            $sottoclassi = $voce['subclasses'] ?? [];
            unset($voce['subclasses']);

            // Nella forma del pannello: un gruppo è {abilities: [...]}.
            $voce['multiclass_requirements'] = array_map(fn (array $gruppo) => ['abilities' => $gruppo], $voce['multiclass_requirements']);

            GameClass::firstOrCreate(['name' => $voce['name']], [...$voce, 'position' => $posizione]);

            foreach ($sottoclassi as $indice => $sottoclasse) {
                Subclass::firstOrCreate(
                    ['class' => $voce['name'], 'name' => $sottoclasse['name']],
                    ['description' => $sottoclasse['description'], 'is_homebrew' => false, 'position' => $indice],
                );
            }
        }
    }
}
