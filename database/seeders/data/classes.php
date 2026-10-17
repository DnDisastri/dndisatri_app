<?php

/*
 * Le classi di partenza, dai file di config della vecchia applicazione
 * (convertiti con tools/convert-data.mjs). Dopo la prima migrazione si
 * cambiano dal pannello.
 */

return [
    [
        'name' => 'Barbaro',
        'hit_die' => 12,
        'saves' => [
            'str',
            'con',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'animalHandling',
            'athletics',
            'intimidation',
            'nature',
            'perception',
            'survival',
        ],
        'proficiencies' => 'Armature leggere e medie, scudi; armi semplici e da guerra.',
        'equipment_summary' => 'Ascia bipenne (o arma da guerra), due ascie (o arma semplice), pacchetto da esploratore, 4 giavellotti.',
        'caster_type' => 'none',
        'spell_ability' => null,
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [],
        'multiclass_requirements' => [
            [
                'str',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [],
            'choices' => [
                [
                    'label' => 'Arma principale',
                    'options' => [
                        [
                            'label' => 'Ascia Bipenne',
                            'items' => [
                                [
                                    'name' => 'Ascia Bipenne',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma da guerra (Spada Lunga)',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Armi secondarie',
                    'options' => [
                        [
                            'label' => 'Due Asce da Battaglia',
                            'items' => [
                                [
                                    'name' => 'Ascia da Battaglia',
                                    'category' => 'Armi',
                                    'qty' => 2,
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Lancia)',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Bardo',
        'hit_die' => 8,
        'saves' => [
            'dex',
            'cha',
        ],
        'skill_count' => 3,
        'skill_choices' => [
            'athletics',
            'acrobatics',
            'sleightOfHand',
            'stealth',
            'arcana',
            'history',
            'investigation',
            'nature',
            'religion',
            'animalHandling',
            'insight',
            'medicine',
            'perception',
            'survival',
            'deception',
            'intimidation',
            'performance',
            'persuasion',
        ],
        'proficiencies' => 'Armature leggere; armi semplici, balestre a mano, spade lunghe, stocchi, spade corte; tre strumenti musicali a scelta.',
        'equipment_summary' => 'Stocco (o arma semplice), pacchetto da intrattenitore, uno strumento musicale, armatura di cuoio, un pugnale.',
        'caster_type' => 'full',
        'spell_ability' => 'cha',
        'cantrips_at_1' => 2,
        'spells_at_1' => 4,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => true,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [
            'Mano Magica',
            'Luce',
            'Prestidigitazione',
            'Illusione Minore',
            'Cura Ferite',
            'Parola Guaritrice',
            'Charme su Persone',
            'Sonno',
            'Comando',
            'Invisibilità',
            'Immagine Speculare',
            'Blocca Persone',
            'Dissolvi Magie',
            'Polimorfia',
        ],
        'multiclass_requirements' => [
            [
                'cha',
            ],
        ],
        'multiclass_skill_count' => 1,
        'multiclass_skill_choices' => [
            'athletics',
            'acrobatics',
            'sleightOfHand',
            'stealth',
            'arcana',
            'history',
            'investigation',
            'nature',
            'religion',
            'animalHandling',
            'insight',
            'medicine',
            'perception',
            'survival',
            'deception',
            'intimidation',
            'performance',
            'persuasion',
        ],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Armatura di Cuoio',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Pugnale',
                    'category' => 'Armi',
                ],
                [
                    'name' => 'Strumento Musicale',
                    'category' => 'Kit e Strumenti',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Stocco (Spada Corta)',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Spada Lunga',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Chierico',
        'hit_die' => 8,
        'saves' => [
            'wis',
            'cha',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'history',
            'insight',
            'medicine',
            'persuasion',
            'religion',
        ],
        'proficiencies' => 'Armature leggere e medie, scudi; armi semplici.',
        'equipment_summary' => 'Mazza, armatura di scaglie (o cuoio), balestra leggera e 20 dardi (o arma semplice), simbolo sacro, pacchetto da sacerdote.',
        'caster_type' => 'full',
        'spell_ability' => 'wis',
        'cantrips_at_1' => 3,
        'spells_at_1' => null,
        'prepares' => true,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 1,
        'spell_list' => [
            'Fiamma Sacra',
            'Luce',
            'Taumaturgia',
            'Guida',
            'Cura Ferite',
            'Parola Guaritrice',
            'Benedizione',
            'Comando',
            'Individuazione del Magico',
            'Ristorare Inferiore',
            'Rivitalizzare',
            'Dissolvi Magie',
            'Rianimare Morti',
        ],
        'multiclass_requirements' => [
            [
                'wis',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Scudo',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Simbolo Sacro',
                    'category' => 'Varie',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Mazza',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Martello da Guerra',
                            'items' => [
                                [
                                    'name' => 'Martello da Guerra',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Armatura',
                    'options' => [
                        [
                            'label' => 'Corazza di Scaglie',
                            'items' => [
                                [
                                    'name' => 'Corazza di Scaglie',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Armatura di Cuoio',
                            'items' => [
                                [
                                    'name' => 'Armatura di Cuoio',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'A distanza',
                    'options' => [
                        [
                            'label' => 'Balestra Leggera',
                            'items' => [
                                [
                                    'name' => 'Balestra Leggera',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Mazza)',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Druido',
        'hit_die' => 8,
        'saves' => [
            'int',
            'wis',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'arcana',
            'animalHandling',
            'insight',
            'medicine',
            'nature',
            'perception',
            'religion',
            'survival',
        ],
        'proficiencies' => 'Armature e scudi non di metallo; clave, randelli, dardi, giavellotti, mazze, bastoni ferrati, scimitarre, falcetti, fionde, lance; kit da erborista.',
        'equipment_summary' => 'Scudo di legno (o arma semplice), scimitarra (o arma da mischia semplice), armatura di cuoio, pacchetto da esploratore, focus druidico.',
        'caster_type' => 'full',
        'spell_ability' => 'wis',
        'cantrips_at_1' => 2,
        'spells_at_1' => null,
        'prepares' => true,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 2,
        'spell_list' => [
            'Guida',
            'Luce',
            'Cura Ferite',
            'Individuazione del Magico',
            'Nube di Nebbia',
            'Frantumare',
            'Volare',
            'Dissolvi Magie',
            'Cono di Freddo',
        ],
        'multiclass_requirements' => [
            [
                'wis',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Armatura di Cuoio',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Kit da Erborista',
                    'category' => 'Kit e Strumenti',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Scudo o arma',
                    'options' => [
                        [
                            'label' => 'Scudo di legno (Scudo)',
                            'items' => [
                                [
                                    'name' => 'Scudo',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Lancia)',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Arma da mischia',
                    'options' => [
                        [
                            'label' => 'Lancia',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Mazza',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Guerriero',
        'hit_die' => 10,
        'saves' => [
            'str',
            'con',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'acrobatics',
            'animalHandling',
            'athletics',
            'history',
            'insight',
            'intimidation',
            'perception',
            'survival',
        ],
        'proficiencies' => 'Tutte le armature, scudi; armi semplici e da guerra.',
        'equipment_summary' => 'Cotta di maglia (o cuoio + arco lungo), arma da guerra e scudo (o due armi da guerra), balestra leggera (o due asce), pacchetto da esploratore.',
        'caster_type' => 'none',
        'spell_ability' => null,
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [],
        'multiclass_requirements' => [
            [
                'str',
                'dex',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [],
            'choices' => [
                [
                    'label' => 'Armatura',
                    'options' => [
                        [
                            'label' => 'Cotta di Maglia',
                            'items' => [
                                [
                                    'name' => 'Cotta di Maglia',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Cuoio + Arco Lungo',
                            'items' => [
                                [
                                    'name' => 'Armatura di Cuoio',
                                    'category' => 'Armature',
                                ],
                                [
                                    'name' => 'Arco Lungo',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Arma principale',
                    'options' => [
                        [
                            'label' => 'Arma da guerra + Scudo',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                                [
                                    'name' => 'Scudo',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Due armi da guerra',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                                [
                                    'name' => 'Ascia da Battaglia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'A distanza',
                    'options' => [
                        [
                            'label' => 'Balestra Leggera',
                            'items' => [
                                [
                                    'name' => 'Balestra Leggera',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Due Asce da Battaglia',
                            'items' => [
                                [
                                    'name' => 'Ascia da Battaglia',
                                    'category' => 'Armi',
                                    'qty' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Ladro',
        'hit_die' => 8,
        'saves' => [
            'dex',
            'int',
        ],
        'skill_count' => 4,
        'skill_choices' => [
            'acrobatics',
            'athletics',
            'deception',
            'insight',
            'intimidation',
            'investigation',
            'perception',
            'performance',
            'persuasion',
            'sleightOfHand',
            'stealth',
        ],
        'proficiencies' => 'Armature leggere; armi semplici, balestre a mano, spade lunghe, stocchi, spade corte; arnesi da scasso.',
        'equipment_summary' => 'Stocco (o spada corta), arco corto e faretra (o spada corta), armatura di cuoio, due pugnali, arnesi da scasso, pacchetto da scassinatore.',
        'caster_type' => 'none',
        'spell_ability' => null,
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [],
        'multiclass_requirements' => [
            [
                'dex',
            ],
        ],
        'multiclass_skill_count' => 1,
        'multiclass_skill_choices' => [
            'acrobatics',
            'athletics',
            'deception',
            'insight',
            'intimidation',
            'investigation',
            'perception',
            'performance',
            'persuasion',
            'sleightOfHand',
            'stealth',
        ],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Armatura di Cuoio',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Pugnale',
                    'category' => 'Armi',
                    'qty' => 2,
                ],
                [
                    'name' => 'Arnesi da Scasso',
                    'category' => 'Kit e Strumenti',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma principale',
                    'options' => [
                        [
                            'label' => 'Stocco (Spada Corta)',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Spada Corta',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'A distanza',
                    'options' => [
                        [
                            'label' => 'Arco Corto',
                            'items' => [
                                [
                                    'name' => 'Arco Corto',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Spada Corta',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Mago',
        'hit_die' => 6,
        'saves' => [
            'int',
            'wis',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'arcana',
            'history',
            'insight',
            'investigation',
            'medicine',
            'religion',
        ],
        'proficiencies' => 'Pugnali, dardi, fionde, bastoni ferrati, balestre leggere.',
        'equipment_summary' => 'Bastone ferrato (o pugnale), focus arcano (o borsa dei componenti), libro degli incantesimi, pacchetto da studioso.',
        'caster_type' => 'full',
        'spell_ability' => 'int',
        'cantrips_at_1' => 3,
        'spells_at_1' => 6,
        'prepares' => true,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => true,
        'subclass_level' => 2,
        'spell_list' => [
            'Dardo di Fuoco',
            'Mano Magica',
            'Luce',
            'Prestidigitazione',
            'Illusione Minore',
            'Raggio di Gelo',
            'Tocco Gelido',
            'Dardo Incantato',
            'Scudo',
            'Armatura Magica',
            'Mani Brucianti',
            'Charme su Persone',
            'Sonno',
            'Individuazione del Magico',
            'Nube di Nebbia',
            'Raggio Rovente',
            'Passo Velato',
            'Invisibilità',
            'Immagine Speculare',
            'Blocca Persone',
            'Frantumare',
            'Palla di Fuoco',
            'Fulmine',
            'Controincantesimo',
            'Dissolvi Magie',
            'Volare',
            'Porta Dimensionale',
            'Polimorfia',
            'Cono di Freddo',
            'Muro di Forza',
        ],
        'multiclass_requirements' => [
            [
                'int',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Libro degli Incantesimi',
                    'category' => 'Varie',
                ],
                [
                    'name' => 'Focus Arcano',
                    'category' => 'Varie',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Bastone Ferrato (Pugnale)',
                            'items' => [
                                [
                                    'name' => 'Pugnale',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Pugnale',
                            'items' => [
                                [
                                    'name' => 'Pugnale',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Monaco',
        'hit_die' => 8,
        'saves' => [
            'str',
            'dex',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'acrobatics',
            'athletics',
            'history',
            'insight',
            'religion',
            'stealth',
        ],
        'proficiencies' => 'Armi semplici e spade corte; uno strumento da artigiano o musicale a scelta.',
        'equipment_summary' => 'Spada corta (o arma semplice), pacchetto da esploratore (o da avventuriero), 10 dardi.',
        'caster_type' => 'none',
        'spell_ability' => null,
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [],
        'multiclass_requirements' => [
            [
                'dex',
            ],
            [
                'wis',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Spada Corta',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Lancia)',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Paladino',
        'hit_die' => 10,
        'saves' => [
            'wis',
            'cha',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'athletics',
            'insight',
            'intimidation',
            'medicine',
            'persuasion',
            'religion',
        ],
        'proficiencies' => 'Tutte le armature, scudi; armi semplici e da guerra.',
        'equipment_summary' => 'Arma da guerra e scudo (o due armi da guerra), cinque giavellotti (o arma da mischia semplice), pacchetto da sacerdote, cotta di maglia, simbolo sacro.',
        'caster_type' => 'half',
        'spell_ability' => 'cha',
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => true,
        'prepare_from_level' => 2,
        'prepare_half_level' => true,
        'swaps_spells' => false,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [
            'Cura Ferite',
            'Benedizione',
            'Comando',
            'Individuazione del Magico',
        ],
        'multiclass_requirements' => [
            [
                'str',
            ],
            [
                'cha',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Cotta di Maglia',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Simbolo Sacro',
                    'category' => 'Varie',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma principale',
                    'options' => [
                        [
                            'label' => 'Arma da guerra + Scudo',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                                [
                                    'name' => 'Scudo',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Due armi da guerra',
                            'items' => [
                                [
                                    'name' => 'Spada Lunga',
                                    'category' => 'Armi',
                                ],
                                [
                                    'name' => 'Ascia da Battaglia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Secondaria',
                    'options' => [
                        [
                            'label' => 'Giavellotti (Lancia)',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma da mischia semplice (Mazza)',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Ranger',
        'hit_die' => 10,
        'saves' => [
            'str',
            'dex',
        ],
        'skill_count' => 3,
        'skill_choices' => [
            'animalHandling',
            'athletics',
            'insight',
            'investigation',
            'nature',
            'perception',
            'stealth',
            'survival',
        ],
        'proficiencies' => 'Armature leggere e medie, scudi; armi semplici e da guerra.',
        'equipment_summary' => 'Armatura di scaglie (o cuoio), due spade corte (o due armi semplici da mischia), arco lungo e faretra, pacchetto da esploratore.',
        'caster_type' => 'half',
        'spell_ability' => 'wis',
        'cantrips_at_1' => 0,
        'spells_at_1' => null,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => true,
        'copies_scrolls' => false,
        'subclass_level' => 3,
        'spell_list' => [
            'Cura Ferite',
            'Nube di Nebbia',
            'Individuazione del Magico',
        ],
        'multiclass_requirements' => [
            [
                'dex',
            ],
            [
                'wis',
            ],
        ],
        'multiclass_skill_count' => 1,
        'multiclass_skill_choices' => [
            'animalHandling',
            'athletics',
            'insight',
            'investigation',
            'nature',
            'perception',
            'stealth',
            'survival',
        ],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Arco Lungo',
                    'category' => 'Armi',
                ],
            ],
            'choices' => [
                [
                    'label' => 'Armatura',
                    'options' => [
                        [
                            'label' => 'Corazza di Scaglie',
                            'items' => [
                                [
                                    'name' => 'Corazza di Scaglie',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Armatura di Cuoio',
                            'items' => [
                                [
                                    'name' => 'Armatura di Cuoio',
                                    'category' => 'Armature',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Armi da mischia',
                    'options' => [
                        [
                            'label' => 'Due Spade Corte',
                            'items' => [
                                [
                                    'name' => 'Spada Corta',
                                    'category' => 'Armi',
                                    'qty' => 2,
                                ],
                            ],
                        ],
                        [
                            'label' => 'Due Lance',
                            'items' => [
                                [
                                    'name' => 'Lancia',
                                    'category' => 'Armi',
                                    'qty' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Stregone',
        'hit_die' => 6,
        'saves' => [
            'con',
            'cha',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'arcana',
            'deception',
            'insight',
            'intimidation',
            'persuasion',
            'religion',
        ],
        'proficiencies' => 'Pugnali, dardi, fionde, bastoni ferrati, balestre leggere.',
        'equipment_summary' => 'Balestra leggera e 20 dardi (o arma semplice), focus arcano (o borsa dei componenti), pacchetto da esploratore (o da studioso), due pugnali.',
        'caster_type' => 'full',
        'spell_ability' => 'cha',
        'cantrips_at_1' => 4,
        'spells_at_1' => 2,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => true,
        'copies_scrolls' => false,
        'subclass_level' => 1,
        'spell_list' => [
            'Dardo di Fuoco',
            'Mano Magica',
            'Luce',
            'Prestidigitazione',
            'Raggio di Gelo',
            'Dardo Incantato',
            'Scudo',
            'Armatura Magica',
            'Mani Brucianti',
            'Charme su Persone',
            'Sonno',
            'Nube di Nebbia',
            'Passo Velato',
            'Invisibilità',
            'Immagine Speculare',
            'Frantumare',
            'Palla di Fuoco',
            'Fulmine',
            'Controincantesimo',
            'Volare',
            'Polimorfia',
            'Cono di Freddo',
        ],
        'multiclass_requirements' => [
            [
                'cha',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Focus Arcano',
                    'category' => 'Varie',
                ],
                [
                    'name' => 'Pugnale',
                    'category' => 'Armi',
                    'qty' => 2,
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Balestra Leggera',
                            'items' => [
                                [
                                    'name' => 'Balestra Leggera',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Mazza)',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name' => 'Warlock',
        'hit_die' => 8,
        'saves' => [
            'wis',
            'cha',
        ],
        'skill_count' => 2,
        'skill_choices' => [
            'arcana',
            'deception',
            'history',
            'intimidation',
            'investigation',
            'nature',
            'religion',
        ],
        'proficiencies' => 'Armature leggere; armi semplici.',
        'equipment_summary' => 'Balestra leggera e 20 dardi (o arma semplice), focus arcano (o borsa dei componenti), armatura di cuoio, arma semplice, due pugnali, pacchetto da studioso.',
        'caster_type' => 'pact',
        'spell_ability' => 'cha',
        'cantrips_at_1' => 2,
        'spells_at_1' => 2,
        'prepares' => false,
        'prepare_from_level' => 1,
        'prepare_half_level' => false,
        'swaps_spells' => true,
        'copies_scrolls' => false,
        'subclass_level' => 1,
        'spell_list' => [
            'Tocco Gelido',
            'Mano Magica',
            'Illusione Minore',
            'Charme su Persone',
            'Passo Velato',
            'Invisibilità',
            'Blocca Persone',
            'Controincantesimo',
            'Dissolvi Magie',
            'Volare',
            'Polimorfia',
        ],
        'multiclass_requirements' => [
            [
                'cha',
            ],
        ],
        'multiclass_skill_count' => 0,
        'multiclass_skill_choices' => [],
        'starting_equipment' => [
            'fixed' => [
                [
                    'name' => 'Armatura di Cuoio',
                    'category' => 'Armature',
                ],
                [
                    'name' => 'Focus Arcano',
                    'category' => 'Varie',
                ],
                [
                    'name' => 'Pugnale',
                    'category' => 'Armi',
                    'qty' => 2,
                ],
            ],
            'choices' => [
                [
                    'label' => 'Arma',
                    'options' => [
                        [
                            'label' => 'Balestra Leggera',
                            'items' => [
                                [
                                    'name' => 'Balestra Leggera',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Arma semplice (Mazza)',
                            'items' => [
                                [
                                    'name' => 'Mazza',
                                    'category' => 'Armi',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
