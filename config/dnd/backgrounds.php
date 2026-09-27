<?php

/*
 * Origine: backgrounds.js, convertito da tools/convert-data.mjs, che non fa
 * più parte del repository: da qui in avanti il file si modifica a mano, come
 * è stato fatto per «Personalizzato» e per gli zaini.
 *
 * Le chiavi interne sono quelle della vecchia applicazione, lasciate
 * identiche di proposito: la conversione resta verificabile 1:1.
 */

return [
    'list' => [
        'Accolito' => [
            'skills' => [
                'insight',
                'religion',
            ],
            'gp' => 15,
            'equip' => 'Simbolo sacro, libro di preghiere, 5 bastoncini d\'incenso, vesti, abiti comuni. 2 linguaggi.',
        ],
        'Ciarlatano' => [
            'skills' => [
                'deception',
                'sleightOfHand',
            ],
            'gp' => 15,
            'equip' => 'Abiti eleganti, kit da travestimento, attrezzatura per una truffa. Strumenti: kit da falsario e da trucco.',
        ],
        'Criminale' => [
            'skills' => [
                'deception',
                'stealth',
            ],
            'gp' => 15,
            'equip' => 'Piede di porco, abiti scuri col cappuccio. Strumenti: un gioco e arnesi da scasso.',
        ],
        'Intrattenitore' => [
            'skills' => [
                'acrobatics',
                'performance',
            ],
            'gp' => 15,
            'equip' => 'Uno strumento musicale, il favore di un ammiratore, un costume. Strumenti: kit da trucco.',
        ],
        'Eroe Popolano' => [
            'skills' => [
                'animalHandling',
                'survival',
            ],
            'gp' => 10,
            'equip' => 'Strumenti da artigiano, una pala, una pentola di ferro, abiti comuni. Competenza: veicoli terrestri.',
        ],
        'Artigiano di Gilda' => [
            'skills' => [
                'insight',
                'persuasion',
            ],
            'gp' => 15,
            'equip' => 'Strumenti da artigiano, lettera di presentazione della gilda, abiti da viaggio. 1 linguaggio.',
        ],
        'Eremita' => [
            'skills' => [
                'medicine',
                'religion',
            ],
            'gp' => 5,
            'equip' => 'Custodia con appunti, coperta, kit da erborista, abiti comuni. 1 linguaggio.',
        ],
        'Nobile' => [
            'skills' => [
                'history',
                'persuasion',
            ],
            'gp' => 25,
            'equip' => 'Abiti eleganti, anello con sigillo, pergamena del casato. Strumenti: un gioco. 1 linguaggio.',
        ],
        'Forestiero' => [
            'skills' => [
                'athletics',
                'survival',
            ],
            'gp' => 10,
            'equip' => 'Un bastone, una trappola, uno strumento musicale, abiti da viaggio. 1 linguaggio.',
        ],
        'Sapiente' => [
            'skills' => [
                'arcana',
                'history',
            ],
            'gp' => 10,
            'equip' => 'Boccetta d\'inchiostro, penna, coltellino, lettera di un collega defunto, abiti comuni. 2 linguaggi.',
        ],
        'Marinaio' => [
            'skills' => [
                'athletics',
                'perception',
            ],
            'gp' => 10,
            'equip' => 'Verga di ferro, corda di seta, portafortuna, abiti comuni. Strumenti: navigatore e veicoli acquatici.',
        ],
        'Soldato' => [
            'skills' => [
                'athletics',
                'intimidation',
            ],
            'gp' => 10,
            'equip' => 'Insegna di grado, trofeo di guerra, un gioco di dadi, abiti comuni. Strumenti: un gioco e veicoli terrestri.',
        ],
        'Monello' => [
            'skills' => [
                'sleightOfHand',
                'stealth',
            ],
            'gp' => 10,
            'equip' => 'Coltellino, mappa della città natale, un topo domestico, un souvenir dei genitori, abiti comuni. Strumenti: kit da trucco e arnesi da scasso.',
        ],

        /*
         * Per chi non si riconosce in nessuno dei tredici.
         *
         * `free_skills` è la differenza: gli altri danno due abilità fisse,
         * questo lascia scegliere quali. Lo zaino si sceglie fra quelli in
         * `packs`, con la stessa logica delle alternative di classe.
         */
        'Personalizzato' => [
            'skills' => [],
            'free_skills' => 2,
            'gp' => 10,
            'equip' => 'Uno zaino a scelta, e quello che ti sei portato dietro dalla tua storia.',
        ],
    ],

    /*
     * Gli zaini del manuale, offerti a chi sceglie il background
     * personalizzato. Gli altri hanno un corredo fisso.
     */
    'packs' => [
        [
            'name' => 'Zaino da Esploratore',
            'contents' => 'Zaino, sacco a pelo, kit da mensa, acciarino, 10 torce, 10 giorni di razioni, otre, 15 m di corda di canapa.',
        ],
        [
            'name' => 'Zaino da Avventuriero',
            'contents' => 'Zaino, piede di porco, martello, 10 pioli da ferro, 10 torce, acciarino, 10 giorni di razioni, otre, 15 m di corda di canapa.',
        ],
        [
            'name' => 'Zaino da Studioso',
            'contents' => 'Zaino, libro di sapienza, boccetta d\'inchiostro, penna d\'oca, 10 fogli di pergamena, sacchetto di sabbia, coltellino.',
        ],
        [
            'name' => 'Zaino da Sacerdote',
            'contents' => 'Zaino, coperta, 10 candele, acciarino, cassetta per le elemosine, 2 blocchetti d\'incenso, turibolo, vesti, 2 giorni di razioni, otre.',
        ],
        [
            'name' => 'Zaino da Intrattenitore',
            'contents' => 'Zaino, sacco a pelo, 2 costumi, 5 candele, 5 giorni di razioni, otre, kit da trucco.',
        ],
        [
            'name' => 'Zaino da Scassinatore',
            'contents' => 'Zaino, 1.000 palline di metallo, 3 m di spago, campanello, 5 candele, piede di porco, martello, 10 pioli da ferro, lanterna cieca, 2 fiaschette d\'olio, 5 giorni di razioni, acciarino, otre, 15 m di corda di canapa.',
        ],
        [
            'name' => 'Zaino da Diplomatico',
            'contents' => 'Cassa, 2 astucci per mappe e pergamene, abiti eleganti, boccetta d\'inchiostro, penna d\'oca, lampada, 2 fiaschette d\'olio, 5 fogli di carta, boccetta di profumo, cera per sigilli, sapone.',
        ],
    ],

    'features' => [
        'Accolito' => 'Rifugio dei Fedeli: templi affini offrono ospitalità a te e ai tuoi compagni.',
        'Ciarlatano' => 'Falsa Identità: possiedi una seconda identità documentata e credibile.',
        'Criminale' => 'Contatto Criminale: hai una rete affidabile nel mondo del crimine.',
        'Intrattenitore' => 'Richiesto ovunque: trovi sempre da esibirti in cambio di vitto e alloggio.',
        'Eroe Popolano' => 'Ospitalità Rustica: la gente comune ti offre riparo e protezione.',
        'Artigiano di Gilda' => 'Appartenenza alla Gilda: sostegno, alloggio e contatti dalla tua gilda.',
        'Eremita' => 'Scoperta: dal tuo isolamento hai appreso un segreto unico e importante.',
        'Nobile' => 'Posizione di Privilegio: sei accolto con rispetto dall\'alta società.',
        'Forestiero' => 'Viandante: ti orienti sempre e puoi procurare cibo per il gruppo.',
        'Sapiente' => 'Ricercatore: sai dove e da chi ottenere le informazioni che ti mancano.',
        'Marinaio' => 'Passaggio in Nave: puoi ottenere un imbarco gratuito per te e i compagni.',
        'Soldato' => 'Grado Militare: i soldati riconoscono la tua autorità di ex commilitone.',
        'Monello' => 'Segreti della Città: ti muovi tra i vicoli al doppio della velocità normale.',
    ],
];
