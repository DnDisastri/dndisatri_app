<?php

/*
 * Oggetti magici di partenza (SRD 5.1, licenza CC-BY-4.0, Wizards of the
 * Coast), con descrizioni brevi scritte da noi. Si cambiano dal pannello.
 *
 * Quelli con +N non hanno un tipo: arma, armatura o scudo li sceglie il DM
 * quando approva il bottino.
 */

return [
    ['name' => 'Pozione di Guarigione', 'rarity' => 'common', 'category' => 'Pozioni', 'value_cp' => 5_000, 'details' => 'Bevendola recuperi 2d4 + 2 punti ferita.'],
    ['name' => 'Pozione di Guarigione Superiore', 'rarity' => 'uncommon', 'category' => 'Pozioni', 'details' => 'Bevendola recuperi 4d4 + 4 punti ferita.'],
    ['name' => 'Pozione di Guarigione Maggiore', 'rarity' => 'rare', 'category' => 'Pozioni Rare', 'details' => 'Bevendola recuperi 8d4 + 8 punti ferita.'],

    ['name' => 'Arma +1', 'rarity' => 'uncommon', 'category' => 'Armi', 'magic_bonus' => 1, 'details' => '+1 ai tiri per colpire e ai danni.'],
    ['name' => 'Arma +2', 'rarity' => 'rare', 'category' => 'Armi', 'magic_bonus' => 2, 'details' => '+2 ai tiri per colpire e ai danni.'],
    ['name' => 'Arma +3', 'rarity' => 'very_rare', 'category' => 'Armi', 'magic_bonus' => 3, 'details' => '+3 ai tiri per colpire e ai danni.'],
    ['name' => 'Armatura +1', 'rarity' => 'rare', 'category' => 'Armature', 'magic_bonus' => 1, 'details' => '+1 alla CA mentre la indossi.'],
    ['name' => 'Armatura +2', 'rarity' => 'very_rare', 'category' => 'Armature', 'magic_bonus' => 2, 'details' => '+2 alla CA mentre la indossi.'],
    ['name' => 'Armatura +3', 'rarity' => 'legendary', 'category' => 'Armature', 'magic_bonus' => 3, 'details' => '+3 alla CA mentre la indossi.'],
    ['name' => 'Scudo +1', 'rarity' => 'uncommon', 'category' => 'Armature', 'base' => 'Scudo', 'magic_bonus' => 1, 'details' => '+1 alla CA oltre al normale bonus dello scudo.'],
    ['name' => 'Scudo +2', 'rarity' => 'rare', 'category' => 'Armature', 'base' => 'Scudo', 'magic_bonus' => 2, 'details' => '+2 alla CA oltre al normale bonus dello scudo.'],
    ['name' => 'Scudo +3', 'rarity' => 'very_rare', 'category' => 'Armature', 'base' => 'Scudo', 'magic_bonus' => 3, 'details' => '+3 alla CA oltre al normale bonus dello scudo.'],

    ['name' => "Guanti della Potenza dell'Orco", 'rarity' => 'uncommon', 'requires_attunement' => true, 'effects' => [['ability' => 'str', 'mode' => 'set', 'value' => 19]], 'details' => 'Finché li indossi la tua Forza è 19, se non è già più alta.'],
    ['name' => "Fascia dell'Intelletto", 'rarity' => 'uncommon', 'requires_attunement' => true, 'effects' => [['ability' => 'int', 'mode' => 'set', 'value' => 19]], 'details' => 'Finché la indossi la tua Intelligenza è 19, se non è già più alta.'],
    ['name' => 'Amuleto della Salute', 'rarity' => 'rare', 'requires_attunement' => true, 'effects' => [['ability' => 'con', 'mode' => 'set', 'value' => 19]], 'details' => 'Finché lo indossi la tua Costituzione è 19, se non è già più alta.'],
    ['name' => 'Cintura della Forza del Gigante delle Colline', 'rarity' => 'rare', 'requires_attunement' => true, 'effects' => [['ability' => 'str', 'mode' => 'set', 'value' => 21]], 'details' => 'Finché la indossi la tua Forza è 21, se non è già più alta.'],

    ['name' => 'Mantello di Protezione', 'rarity' => 'uncommon', 'requires_attunement' => true, 'details' => '+1 alla CA e ai tiri salvezza finché lo indossi. Da aggiungere a mano: la scheda non lo calcola.'],
    ['name' => 'Borsa Conservante', 'rarity' => 'uncommon', 'details' => "Dentro c'è molto più spazio di quanto sembri: fino a 250 kg, ma pesa sempre 7,5 kg."],
];
