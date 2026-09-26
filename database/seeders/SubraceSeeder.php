<?php

namespace Database\Seeders;

use App\Models\Subrace;
use Illuminate\Database\Seeder;

/**
 * Le sottorazze del Manuale del Giocatore.
 *
 * Tre comportamenti sotto la stessa forma. Le sottorazze vere aggiungono
 * bonus alle caratteristiche, che si **sommano** a quelli della razza: un
 * elfo alto ha Destrezza +2 dall'elfo e Intelligenza +1 dalla sottorazza. La
 * discendenza draconica non tocca i punteggi e decide soffio e resistenza.
 * Le etnie umane sono descrittive e non cambiano niente.
 *
 * Quello che l'applicazione calcola sta in `asi` e `speed`; il resto è in
 * `traits`, come già sono i tratti delle razze.
 */
class SubraceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::catalogo() as $razza => $sottorazze) {
            foreach (array_values($sottorazze) as $posizione => $sottorazza) {
                Subrace::updateOrCreate(
                    ['race' => $razza, 'name' => $sottorazza['name']],
                    [
                        'description' => $sottorazza['description'] ?? null,
                        'asi' => $sottorazza['asi'] ?? [],
                        'speed' => $sottorazza['speed'] ?? null,
                        'traits' => $sottorazza['traits'] ?? null,
                        'is_homebrew' => false,
                        'position' => $posizione,
                    ],
                );
            }
        }
    }

    /** @return array<string,list<array<string,mixed>>> */
    private static function catalogo(): array
    {
        return [
            'Nano' => [
                [
                    'name' => 'Nano delle Colline',
                    'description' => 'Tempra e intuito: il nano che resiste più a lungo.',
                    'asi' => ['wis' => 1],
                    'traits' => '+1 Saggezza. Robustezza Nanica: punti ferita massimi +1 per livello.',
                ],
                [
                    'name' => 'Nano delle Montagne',
                    'description' => 'Cresciuto in alta quota, abituato al peso dell\'acciaio.',
                    'asi' => ['str' => 2],
                    'traits' => '+2 Forza. Competenza nelle armature leggere e medie.',
                ],
            ],

            'Elfo' => [
                [
                    'name' => 'Elfo Alto',
                    'description' => 'Studio e tradizione: un pizzico di magia arcana di famiglia.',
                    'asi' => ['int' => 1],
                    'traits' => '+1 Intelligenza. Competenza con una fra spada lunga, spada corta, '
                        .'arco corto e arco lungo. Un trucchetto da mago. Un linguaggio in più.',
                ],
                [
                    'name' => 'Elfo dei Boschi',
                    'description' => 'Passo lungo e silenzioso, a casa fra gli alberi.',
                    'asi' => ['wis' => 1],
                    'speed' => 10.5,
                    'traits' => '+1 Saggezza. Competenza con le armi elfiche. Velocità 10,5 m. '
                        .'Può nascondersi anche solo leggermente oscurato da fenomeni naturali.',
                ],
                [
                    'name' => 'Drow',
                    'description' => 'Elfo oscuro: occhi fatti per il sottosuolo, e magia con sé.',
                    'asi' => ['cha' => 1],
                    'traits' => '+1 Carisma. Scurovisione superiore 36 m. Sensibilità alla luce del sole. '
                        .'Competenza con stocco, spada corta e balestra a mano. Magia drow.',
                ],
            ],

            'Halfling' => [
                [
                    'name' => 'Piedelesto',
                    'description' => 'Sfacciato e svelto, sparisce dietro chiunque sia più grosso.',
                    'asi' => ['cha' => 1],
                    'traits' => '+1 Carisma. Può nascondersi dietro una creatura di almeno una taglia più grande.',
                ],
                [
                    'name' => 'Tozzo',
                    'description' => 'Corporatura solida e stomaco che regge tutto.',
                    'asi' => ['con' => 1],
                    'traits' => '+1 Costituzione. Resistenza ai danni da veleno e vantaggio ai tiri salvezza contro il veleno.',
                ],
            ],

            'Gnomo' => [
                [
                    'name' => 'Gnomo delle Foreste',
                    'description' => 'Piccole illusioni e confidenza con gli animali del bosco.',
                    'asi' => ['dex' => 1],
                    'traits' => '+1 Destrezza. Conosce Illusione Minore. Può comunicare concetti semplici '
                        .'con i piccoli animali.',
                ],
                [
                    'name' => 'Gnomo delle Rocce',
                    'description' => 'Inventore e artigiano: capisce come sono fatte le cose.',
                    'asi' => ['con' => 1],
                    'traits' => '+1 Costituzione. Competenza raddoppiata nelle prove di Intelligenza su oggetti '
                        .'magici, alchemici o tecnologici. Può costruire piccoli congegni.',
                ],
            ],

            // La discendenza non tocca i punteggi: il dragonide tiene +2 Forza
            // e +1 Carisma della razza. Decide soffio e resistenza.
            'Dragonide' => [
                ['name' => 'Nero', 'traits' => 'Acido. Soffio: linea di 1,5 × 9 m, tiro salvezza su Destrezza. Resistenza all\'acido.'],
                ['name' => 'Argento', 'traits' => 'Freddo. Soffio: cono di 4,5 m, tiro salvezza su Costituzione. Resistenza al freddo.'],
                ['name' => 'Bianco', 'traits' => 'Freddo. Soffio: cono di 4,5 m, tiro salvezza su Costituzione. Resistenza al freddo.'],
                ['name' => 'Blu', 'traits' => 'Fulmine. Soffio: linea di 1,5 × 9 m, tiro salvezza su Destrezza. Resistenza al fulmine.'],
                ['name' => 'Bronzo', 'traits' => 'Fulmine. Soffio: linea di 1,5 × 9 m, tiro salvezza su Destrezza. Resistenza al fulmine.'],
                ['name' => 'Oro', 'traits' => 'Fuoco. Soffio: cono di 4,5 m, tiro salvezza su Destrezza. Resistenza al fuoco.'],
                ['name' => 'Ottone', 'traits' => 'Fuoco. Soffio: linea di 1,5 × 9 m, tiro salvezza su Destrezza. Resistenza al fuoco.'],
                ['name' => 'Rame', 'traits' => 'Acido. Soffio: linea di 1,5 × 9 m, tiro salvezza su Destrezza. Resistenza all\'acido.'],
                ['name' => 'Rosso', 'traits' => 'Fuoco. Soffio: cono di 4,5 m, tiro salvezza su Destrezza. Resistenza al fuoco.'],
                ['name' => 'Verde', 'traits' => 'Veleno. Soffio: cono di 4,5 m, tiro salvezza su Costituzione. Resistenza al veleno.'],
            ],

            // Descrittive: non cambiano niente, servono a dire da dove vieni.
            'Umano' => [
                ['name' => 'Nessuna', 'description' => 'Da nessun posto in particolare, o da uno che ti inventi tu.'],
                ['name' => 'Calishita'],
                ['name' => 'Chondathano'],
                ['name' => 'Damarano'],
                ['name' => 'Illuskano'],
                ['name' => 'Mulan'],
                ['name' => 'Rashemi'],
                ['name' => 'Shou'],
                ['name' => 'Tethyriano'],
                ['name' => 'Turami'],
            ],
        ];
    }
}
