<?php

/*
 * Regole scritte a mano (non generate): i numeri che il gruppo potrebbe voler
 * cambiare stanno qui, in un punto solo.
 */

return [
    // Chi prepara gli incantesimi ogni giorno: caratteristica + livello nella
    // classe (o metà livello, arrotondato per difetto), da quale livello.
    'preparation' => [
        'Chierico' => ['from_level' => 1, 'half_level' => false],
        'Druido' => ['from_level' => 1, 'half_level' => false],
        'Mago' => ['from_level' => 1, 'half_level' => false],
        'Paladino' => ['from_level' => 2, 'half_level' => true],
    ],

    // Chi conosce un numero fisso di incantesimi e, salendo di livello in
    // quella classe, può sostituirne uno.
    'spell_swap' => ['Bardo', 'Stregone', 'Warlock', 'Ranger'],
];
