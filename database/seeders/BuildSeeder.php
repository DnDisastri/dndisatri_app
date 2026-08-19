<?php

namespace Database\Seeders;

use App\Models\Build;
use App\Models\User;
use Database\Seeders\Support\Placeholder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Tre build consigliate **complete**, per la demo (P43/P44).
 *
 * Complete vuol dire che bastano a riempire un personaggio di 1° senza domande
 * (`Build::isComplete()`): specie, background, punteggi e abilità ci sono. Le
 * otto storiche ereditate dalla vecchia applicazione non lo erano — solo classe
 * e sottoclasse — e qui servono invece a vedere il pulsante «usa questa build»
 * che funziona davvero.
 *
 * Coprono i due casi del mago della creazione: senza incantesimi (Guerriero,
 * Ladro) e con (Mago). Le scrive un dungeon master; qui l'autore è il primo DM
 * che c'è, o nessuno.
 */
class BuildSeeder extends Seeder
{
    public function run(): void
    {
        // Il ruolo dev'esistere prima di interrogarlo, o `User::role()` esplode.
        // Nel DemoSeeder RoleSeeder gira già prima; qui è per stare in piedi da
        // solo, come DevUserSeeder.
        $this->call(RoleSeeder::class);

        $autore = User::role(User::ROLE_DM)->orderBy('id')->first();

        $builds = [
            [
                'title' => 'Il Muro',
                'tag' => 'Semplice · Robusto',
                'summary' => 'Il personaggio più facile da giocare bene: poche decisioni, tanta tenuta. Ti metti davanti, tiri, e reggi i colpi che gli altri non possono permettersi.',
                'abilities_advice' => 'Forza prima di tutto, poi Costituzione. La Destrezza aiuta la Classe Armatura finché non trovi un\'armatura pesante.',
                'progression' => 'Al 2° «Azione Impetuosa» per un turno in più; al 3° il Campione rende i critici più facili. Al 4° e all\'8° alza la Forza fino a 20.',
                'class' => 'Guerriero',
                'subclass' => 'Campione',
                'species' => 'Nano',
                'background' => 'Soldato',
                'scores' => ['str' => 15, 'dex' => 13, 'con' => 14, 'int' => 8, 'wis' => 12, 'cha' => 10],
                'skills' => ['athletics', 'perception'],
                'equipment' => [0 => 0, 1 => 0, 2 => 0],
                'spells' => [],
            ],
            [
                'title' => 'L\'Ombra',
                'tag' => 'Furtivo · Danni',
                'summary' => 'Colpisce forte dai lati e sparisce. Tante abilità, poco da imparare a memoria: l\'esploratore e il ladro del gruppo.',
                'abilities_advice' => 'Destrezza su tutto: attacco, Classe Armatura, furtività e iniziativa. Poi Costituzione per restare in piedi.',
                'progression' => 'L\'Attacco Furtivo cresce a ogni livello dispari. Al 3° l\'Assassino colpisce durissimo chi non ha ancora agito. Al 4° e all\'8° alza la Destrezza.',
                'class' => 'Ladro',
                'subclass' => 'Assassino',
                'species' => 'Halfling',
                'background' => 'Criminale',
                'scores' => ['str' => 8, 'dex' => 15, 'con' => 14, 'int' => 13, 'wis' => 12, 'cha' => 10],
                'skills' => ['stealth', 'acrobatics', 'perception', 'investigation'],
                'equipment' => [0 => 0, 1 => 0],
                'spells' => [],
            ],
            [
                'title' => 'Il Sapiente',
                'tag' => 'Versatile · Magia',
                'summary' => 'Poca tenuta, tante risposte: un incantesimo per ogni problema. Va tenuto lontano dai colpi, ma cambia le battaglie da solo.',
                'abilities_advice' => 'Intelligenza prima di tutto: è attacco e difesa dei tuoi incantesimi. Poi Costituzione e Destrezza per sopravvivere.',
                'progression' => 'Impari incantesimi nuovi a ogni livello. Al 3° la scuola di Evocazione porta creature al tuo fianco. Al 4° e all\'8° alza l\'Intelligenza.',
                'class' => 'Mago',
                'subclass' => 'Evocazione',
                'species' => 'Gnomo',
                'background' => 'Sapiente',
                'scores' => ['str' => 8, 'dex' => 14, 'con' => 14, 'int' => 15, 'wis' => 12, 'cha' => 8],
                'skills' => ['arcana', 'history'],
                'equipment' => [0 => 0],
                'spells' => ['Mano Magica', 'Luce', 'Dardo Incantato', 'Scudo'],
            ],
        ];

        foreach ($builds as $dati) {
            Build::updateOrCreate(
                ['slug' => Str::slug($dati['title'])],
                [
                    ...$dati,
                    'slug' => Str::slug($dati['title']),
                    'cover_path' => Placeholder::make('build', $dati['title']),
                    'created_by' => $autore?->getKey(),
                    'published_at' => now()->subDay(),
                ],
            );
        }

        $this->command?->info('Tre build consigliate complete pronte.');
    }
}
