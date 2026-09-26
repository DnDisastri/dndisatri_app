<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/** FAQ di partenza da `seeders/data/faq.php`; la posizione segue l'ordine dell'elenco. */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('seeders/data/faq.php') as $posizione => $voce) {
            Faq::updateOrCreate(
                ['question' => $voce['question']],
                [
                    'category' => $voce['category'],
                    'answer' => $voce['answer'],
                    'position' => $posizione,
                    'is_published' => true,
                ],
            );
        }

        $this->command?->info('Guida: '.Faq::count().' voci.');
    }
}
