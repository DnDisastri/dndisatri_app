<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use Illuminate\Database\Seeder;

/**
 * Il testo di partenza di «Chi siamo». Non riscrive: se la riga c'è già, gli
 * admin l'hanno modificata dal pannello e non va toccata.
 */
class AboutSeeder extends Seeder
{
    public function run(): void
    {
        if (AboutPage::exists()) {
            return;
        }

        AboutPage::create([
            'body' => "Siamo un gruppo di appassionati di giochi di ruolo.\n\n"
                ."Ci troviamo per giocare, raccontare storie e passare tempo insieme. "
                ."Questo testo si modifica dal pannello: raccontate chi siete.",
            'socials' => [],
        ]);
    }
}
