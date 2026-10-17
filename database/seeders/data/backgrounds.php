<?php

/*
 * I background di partenza. Quelli 2014 vengono dalla vecchia applicazione;
 * quelli 2024 dall'SRD 5.2.1 (CC-BY-4.0, Wizards of the Coast) con testi
 * nostri. Dopo la prima migrazione si cambiano dal pannello.
 */

return [
    [
        'name' => 'Accolito',
        'rules_edition' => '2014',
        'skills' => [
            'insight',
            'religion',
        ],
        'free_skills' => 0,
        'gp' => 15,
        'equipment_summary' => 'Simbolo sacro, libro di preghiere, 5 bastoncini d\'incenso, vesti, abiti comuni. 2 linguaggi.',
        'feature' => 'Rifugio dei Fedeli: templi affini offrono ospitalità a te e ai tuoi compagni.',
        'starting_items' => [
            [
                'name' => 'Simbolo Sacro',
                'qty' => 1,
                'category' => 'Varie',
            ],
            [
                'name' => 'Bastoncini d\'Incenso',
                'qty' => 5,
                'category' => 'Varie',
            ],
        ],
    ],
    [
        'name' => 'Ciarlatano',
        'rules_edition' => '2014',
        'skills' => [
            'deception',
            'sleightOfHand',
        ],
        'free_skills' => 0,
        'gp' => 15,
        'equipment_summary' => 'Abiti eleganti, kit da travestimento, attrezzatura per una truffa. Strumenti: kit da falsario e da trucco.',
        'feature' => 'Falsa Identità: possiedi una seconda identità documentata e credibile.',
        'starting_items' => [
            [
                'name' => 'Kit da Falsario',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
            [
                'name' => 'Kit da Trucco',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Criminale',
        'rules_edition' => '2014',
        'skills' => [
            'deception',
            'stealth',
        ],
        'free_skills' => 0,
        'gp' => 15,
        'equipment_summary' => 'Piede di porco, abiti scuri col cappuccio. Strumenti: un gioco e arnesi da scasso.',
        'feature' => 'Contatto Criminale: hai una rete affidabile nel mondo del crimine.',
        'starting_items' => [
            [
                'name' => 'Arnesi da Scasso',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
            [
                'name' => 'Piede di Porco',
                'qty' => 1,
                'category' => 'Varie',
            ],
        ],
    ],
    [
        'name' => 'Intrattenitore',
        'rules_edition' => '2014',
        'skills' => [
            'acrobatics',
            'performance',
        ],
        'free_skills' => 0,
        'gp' => 15,
        'equipment_summary' => 'Uno strumento musicale, il favore di un ammiratore, un costume. Strumenti: kit da trucco.',
        'feature' => 'Richiesto ovunque: trovi sempre da esibirti in cambio di vitto e alloggio.',
        'starting_items' => [
            [
                'name' => 'Strumento Musicale',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
            [
                'name' => 'Kit da Trucco',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Eroe Popolano',
        'rules_edition' => '2014',
        'skills' => [
            'animalHandling',
            'survival',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Strumenti da artigiano, una pala, una pentola di ferro, abiti comuni. Competenza: veicoli terrestri.',
        'feature' => 'Ospitalità Rustica: la gente comune ti offre riparo e protezione.',
        'starting_items' => [
            [
                'name' => 'Strumenti da Artigiano',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Artigiano di Gilda',
        'rules_edition' => '2014',
        'skills' => [
            'insight',
            'persuasion',
        ],
        'free_skills' => 0,
        'gp' => 15,
        'equipment_summary' => 'Strumenti da artigiano, lettera di presentazione della gilda, abiti da viaggio. 1 linguaggio.',
        'feature' => 'Appartenenza alla Gilda: sostegno, alloggio e contatti dalla tua gilda.',
        'starting_items' => [
            [
                'name' => 'Strumenti da Artigiano',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Eremita',
        'rules_edition' => '2014',
        'skills' => [
            'medicine',
            'religion',
        ],
        'free_skills' => 0,
        'gp' => 5,
        'equipment_summary' => 'Custodia con appunti, coperta, kit da erborista, abiti comuni. 1 linguaggio.',
        'feature' => 'Scoperta: dal tuo isolamento hai appreso un segreto unico e importante.',
        'starting_items' => [
            [
                'name' => 'Kit da Erborista',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Nobile',
        'rules_edition' => '2014',
        'skills' => [
            'history',
            'persuasion',
        ],
        'free_skills' => 0,
        'gp' => 25,
        'equipment_summary' => 'Abiti eleganti, anello con sigillo, pergamena del casato. Strumenti: un gioco. 1 linguaggio.',
        'feature' => 'Posizione di Privilegio: sei accolto con rispetto dall\'alta società.',
        'starting_items' => [
            [
                'name' => 'Anello con Sigillo',
                'qty' => 1,
                'category' => 'Varie',
            ],
        ],
    ],
    [
        'name' => 'Forestiero',
        'rules_edition' => '2014',
        'skills' => [
            'athletics',
            'survival',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Un bastone, una trappola, uno strumento musicale, abiti da viaggio. 1 linguaggio.',
        'feature' => 'Viandante: ti orienti sempre e puoi procurare cibo per il gruppo.',
        'starting_items' => [
            [
                'name' => 'Strumento Musicale',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Sapiente',
        'rules_edition' => '2014',
        'skills' => [
            'arcana',
            'history',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Boccetta d\'inchiostro, penna, coltellino, lettera di un collega defunto, abiti comuni. 2 linguaggi.',
        'feature' => 'Ricercatore: sai dove e da chi ottenere le informazioni che ti mancano.',
        'starting_items' => [],
    ],
    [
        'name' => 'Marinaio',
        'rules_edition' => '2014',
        'skills' => [
            'athletics',
            'perception',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Verga di ferro, corda di seta, portafortuna, abiti comuni. Strumenti: navigatore e veicoli acquatici.',
        'feature' => 'Passaggio in Nave: puoi ottenere un imbarco gratuito per te e i compagni.',
        'starting_items' => [
            [
                'name' => 'Strumenti da Navigatore',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
            [
                'name' => 'Corda di Seta',
                'qty' => 1,
                'category' => 'Varie',
            ],
        ],
    ],
    [
        'name' => 'Soldato',
        'rules_edition' => '2014',
        'skills' => [
            'athletics',
            'intimidation',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Insegna di grado, trofeo di guerra, un gioco di dadi, abiti comuni. Strumenti: un gioco e veicoli terrestri.',
        'feature' => 'Grado Militare: i soldati riconoscono la tua autorità di ex commilitone.',
        'starting_items' => [
            [
                'name' => 'Insegna di Grado',
                'qty' => 1,
                'category' => 'Varie',
            ],
        ],
    ],
    [
        'name' => 'Monello',
        'rules_edition' => '2014',
        'skills' => [
            'sleightOfHand',
            'stealth',
        ],
        'free_skills' => 0,
        'gp' => 10,
        'equipment_summary' => 'Coltellino, mappa della città natale, un topo domestico, un souvenir dei genitori, abiti comuni. Strumenti: kit da trucco e arnesi da scasso.',
        'feature' => 'Segreti della Città: ti muovi tra i vicoli al doppio della velocità normale.',
        'starting_items' => [
            [
                'name' => 'Kit da Trucco',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
            [
                'name' => 'Arnesi da Scasso',
                'qty' => 1,
                'category' => 'Kit e Strumenti',
            ],
        ],
    ],
    [
        'name' => 'Personalizzato',
        'rules_edition' => '2014',
        'skills' => [],
        'free_skills' => 2,
        'gp' => 10,
        'equipment_summary' => 'Uno zaino a scelta, e quello che ti sei portato dietro dalla tua storia.',
        'feature' => null,
        'starting_items' => [],
    ],
];
