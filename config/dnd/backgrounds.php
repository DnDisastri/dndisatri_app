<?php

/*
 * Origine: backgrounds.js, convertito da tools/convert-data.mjs, che non fa
 * più parte del repository: da qui in avanti il file si modifica a mano, come
 * è stato fatto per «Personalizzato» e per gli zaini.
 *
 * I background sono nella tabella `backgrounds`, modificabile dal pannello:
 * qui restano gli zaini fra cui sceglie il background personalizzato.
 */

return [
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
];
