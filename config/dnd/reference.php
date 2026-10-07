<?php

/*
 * La consultazione rapida del Manuale del DM. Riassunti brevi delle regole
 * dell'SRD 5.1 (CC-BY-4.0); per i dettagli vale il manuale.
 */

return [
    'conditions' => [
        'blinded' => 'Non vede: fallisce le prove che richiedono la vista. I suoi attacchi hanno svantaggio, quelli contro di lui vantaggio.',
        'charmed' => 'Non può attaccare chi l\'ha affascinato; quello ha vantaggio nelle prove sociali contro di lui.',
        'deafened' => 'Non sente: fallisce le prove che richiedono l\'udito.',
        'exhaustion' => 'Sei livelli cumulativi: svantaggio alle prove, velocità dimezzata, svantaggio ad attacchi e TS, PF massimi dimezzati, velocità 0, morte.',
        'frightened' => 'Svantaggio a prove e attacchi finché vede la fonte della paura; non può avvicinarsi a lei.',
        'grappled' => 'Velocità 0. Finisce se chi afferra è incapacitato o se viene allontanato.',
        'incapacitated' => 'Niente azioni né reazioni.',
        'invisible' => 'Non si vede senza magia o sensi speciali. I suoi attacchi hanno vantaggio, quelli contro di lui svantaggio.',
        'paralyzed' => 'Incapacitato, non si muove né parla. Fallisce TS di Forza e Destrezza; gli attacchi contro hanno vantaggio e da 1,5 m sono critici.',
        'petrified' => 'Trasformato in pietra: incapacitato, resistenza a tutti i danni, immune a veleno e malattie.',
        'poisoned' => 'Svantaggio ai tiri per colpire e alle prove di caratteristica.',
        'prone' => 'Si muove solo strisciando; svantaggio ai suoi attacchi. Contro di lui: vantaggio da 1,5 m, svantaggio da lontano.',
        'restrained' => 'Velocità 0. Svantaggio ai suoi attacchi e ai TS di Destrezza; vantaggio agli attacchi contro.',
        'stunned' => 'Incapacitato, parla a fatica. Fallisce TS di Forza e Destrezza; vantaggio agli attacchi contro.',
        'unconscious' => 'Incapacitato, cade prono e lascia ciò che tiene. Fallisce TS di Forza e Destrezza; da 1,5 m i colpi sono critici.',
    ],

    'difficulty' => [
        'Molto facile' => 5,
        'Facile' => 10,
        'Media' => 15,
        'Difficile' => 20,
        'Molto difficile' => 25,
        'Quasi impossibile' => 30,
    ],

    // Passo: al minuto, all'ora, al giorno (8 ore di marcia).
    'travel' => [
        'Veloce' => ['minuto' => '120 m', 'ora' => '6 km', 'giorno' => '45 km', 'nota' => '-5 alla Saggezza (Percezione) passiva'],
        'Normale' => ['minuto' => '90 m', 'ora' => '4,5 km', 'giorno' => '36 km', 'nota' => ''],
        'Lento' => ['minuto' => '60 m', 'ora' => '3 km', 'giorno' => '27 km', 'nota' => 'Si può muovere furtivamente'],
    ],

    // Costo al giorno, in rame.
    'lifestyle' => [
        'Miserabile' => 0,
        'Squallido' => 10,
        'Povero' => 20,
        'Modesto' => 100,
        'Agiato' => 200,
        'Ricco' => 400,
        'Aristocratico' => 1_000,
    ],
];
