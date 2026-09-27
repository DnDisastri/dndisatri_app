<?php

namespace Database\Seeders;

use App\Models\Subclass;
use Illuminate\Database\Seeder;

/**
 * Porta in tabella il catalogo di `config/dnd/subclasses.php`.
 *
 * Quel file resta dov'è: è la conversione 1:1 dei dati della vecchia
 * applicazione, e tenerlo come sorgente del seed lascia la conversione
 * verificabile. Da qui in poi però chi legge sono le righe.
 */
class SubclassSeeder extends Seeder
{
    public function run(): void
    {
        $terzi = config('dnd.classes.third_caster_subclasses', []);

        foreach (config('dnd.subclasses', []) as $classe => $sottoclassi) {
            foreach (array_values($sottoclassi) as $posizione => $sottoclasse) {
                Subclass::updateOrCreate(
                    ['class' => $classe, 'name' => $sottoclasse['name']],
                    [
                        'description' => $sottoclasse['desc'] ?? null,
                        'third_caster' => in_array($sottoclasse['name'], $terzi, true),
                        'is_homebrew' => false,
                        'position' => $posizione,
                    ],
                );
            }
        }
    }
}
