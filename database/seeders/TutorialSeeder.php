<?php

namespace Database\Seeders;

use App\Models\TutorialStep;
use Illuminate\Database\Seeder;

/**
 * Passi di partenza da `seeders/data/tutorial.php`. Non riscrive: se ci sono
 * già passi non tocca niente, così le modifiche dal pannello restano.
 */
class TutorialSeeder extends Seeder
{
    public function run(): void
    {
        if (TutorialStep::exists()) {
            return;
        }

        foreach (require database_path('seeders/data/tutorial.php') as $posizione => $passo) {
            TutorialStep::create([
                'illustration' => $passo['illustration'],
                'title' => $passo['title'],
                'body' => $passo['body'],
                'position' => $posizione,
                'is_published' => true,
            ]);
        }

        $this->command?->info('Tutorial: '.TutorialStep::count().' passi.');
    }
}
