<?php

/*
 * Le specie di partenza, dai file di config della vecchia applicazione
 * (convertiti con tools/convert-data.mjs). Dopo la prima migrazione si
 * cambiano dal pannello.
 */

return [
    [
        'name' => 'Umano',
        'asi' => [
            'all' => 1,
        ],
        'speed' => 9,
        'traits' => 'Versatile: +1 a tutte le caratteristiche. Velocità 9 m.',
        'description' => 'Adattabili e ambiziosi, senza un dono innato ma capaci in tutto: la specie che si trova ovunque.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Umano (Variante)',
        'asi' => [],
        'speed' => 9,
        'traits' => '+1 a due caratteristiche a scelta, una competenza in un\'abilità e un talento (al livello 1). Velocità 9 m.',
        'description' => 'Come l\'umano, ma con un talento precoce: sceglie presto la propria strada.',
        'free_bonuses' => 2,
        'skill_choices' => 1,
        'grants_feat' => true,
    ],
    [
        'name' => 'Nano',
        'asi' => [
            'con' => 2,
        ],
        'speed' => 7.5,
        'traits' => '+2 Costituzione. Scurovisione 18 m. Resistenza al veleno. Competenza con asce e martelli. Velocità 7,5 m.',
        'description' => 'Gente di pietra e ferro, tenace e leale, a suo agio nelle profondità.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Elfo',
        'asi' => [
            'dex' => 2,
        ],
        'speed' => 9,
        'traits' => '+2 Destrezza. Scurovisione 18 m. Vantaggio contro ammaliamento e immunità al sonno magico. Percezione competente.',
        'description' => 'Longevi e aggraziati, legati alla magia e ai boschi. Vedono al buio e non dormono davvero: meditano.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Halfling',
        'asi' => [
            'dex' => 2,
        ],
        'speed' => 7.5,
        'traits' => '+2 Destrezza. Fortunato: ritira gli 1 sui d20 di attacco/prova/TS. Vantaggio contro lo spavento. Velocità 7,5 m.',
        'description' => 'Piccoli, pratici e imperturbabili, con una fortuna sfacciata che li tira fuori dai guai.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Dragonide',
        'asi' => [
            'str' => 2,
            'cha' => 1,
        ],
        'speed' => 9,
        'traits' => '+2 Forza, +1 Carisma. Arma del soffio e resistenza a un tipo di danno in base al lignaggio.',
        'description' => 'Discendenti dei draghi: squame, un soffio elementale e l\'orgoglio del proprio lignaggio.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Gnomo',
        'asi' => [
            'int' => 2,
        ],
        'speed' => 7.5,
        'traits' => '+2 Intelligenza. Scurovisione 18 m. Vantaggio ai TS di INT/SAG/CAR contro la magia. Velocità 7,5 m.',
        'description' => 'Minuti e curiosi fino all\'ossessione, mente svelta e una difesa naturale contro la magia.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Mezzelfo',
        'asi' => [
            'cha' => 2,
        ],
        'speed' => 9,
        'traits' => '+2 Carisma e +1 a due caratteristiche a scelta (da assegnare a mano). Scurovisione. Due competenze in abilità.',
        'description' => 'Fra due mondi e in nessuno del tutto: il carisma dell\'elfo e l\'adattabilità dell\'umano.',
        'free_bonuses' => 2,
        'skill_choices' => 2,
        'grants_feat' => false,
    ],
    [
        'name' => 'Mezzorco',
        'asi' => [
            'str' => 2,
            'con' => 1,
        ],
        'speed' => 9,
        'traits' => '+2 Forza, +1 Costituzione. Scurovisione. Resistenza implacabile (resti a 1 PF una volta al giorno). Attacchi brutali.',
        'description' => 'Forza da orco e cuore da umano: difficili da abbattere, temuti prima ancora di parlare.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
    [
        'name' => 'Tiefling',
        'asi' => [
            'cha' => 2,
            'int' => 1,
        ],
        'speed' => 9,
        'traits' => '+2 Carisma, +1 Intelligenza. Scurovisione. Resistenza al fuoco. Conosce il trucchetto Taumaturgia.',
        'description' => 'Segnati da un lignaggio infernale: corna, resistenza al fuoco e sguardi diffidenti addosso.',
        'free_bonuses' => 0,
        'skill_choices' => 0,
        'grants_feat' => false,
    ],
];
