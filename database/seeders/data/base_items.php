<?php

/*
 * Armi, armature e scudi di partenza (SRD 5.1, licenza CC-BY-4.0, Wizards of
 * the Coast). Prezzi in rame. Dopo la prima migrazione si cambiano dal pannello.
 *
 * «Arma +1» e «Scudo +1» vengono dalla vecchia applicazione: restano perché
 * qualche oggetto potrebbe usarli, ma non compaiono fra i tipi da scegliere
 * (il +N oggi si scrive a parte).
 */

return [
    ['kind' => 'armor', 'name' => 'Armatura Imbottita', 'armor_type' => 'light', 'armor_base' => 11, 'stealth_disadvantage' => true, 'price_cp' => 500],
    ['kind' => 'armor', 'name' => 'Armatura di Cuoio', 'armor_type' => 'light', 'armor_base' => 11, 'price_cp' => 1_000],
    ['kind' => 'armor', 'name' => 'Cuoio Borchiato', 'armor_type' => 'light', 'armor_base' => 12, 'price_cp' => 4_500],
    ['kind' => 'armor', 'name' => 'Corazza di Scaglie', 'armor_type' => 'medium', 'armor_base' => 14, 'dex_cap' => 2, 'stealth_disadvantage' => true, 'price_cp' => 5_000],
    ['kind' => 'armor', 'name' => 'Corpetto (Corazza a Bande)', 'armor_type' => 'medium', 'armor_base' => 14, 'dex_cap' => 2, 'price_cp' => 40_000],
    ['kind' => 'armor', 'name' => 'Mezza Armatura', 'armor_type' => 'medium', 'armor_base' => 15, 'dex_cap' => 2, 'stealth_disadvantage' => true, 'price_cp' => 75_000],
    ['kind' => 'armor', 'name' => 'Cotta di Maglia', 'armor_type' => 'heavy', 'armor_base' => 16, 'dex_cap' => 0, 'stealth_disadvantage' => true, 'strength_required' => 13, 'price_cp' => 7_500],
    ['kind' => 'armor', 'name' => 'Armatura a Piastre', 'armor_type' => 'heavy', 'armor_base' => 18, 'dex_cap' => 0, 'stealth_disadvantage' => true, 'strength_required' => 15, 'price_cp' => 150_000],

    ['kind' => 'shield', 'name' => 'Scudo', 'shield_bonus' => 2, 'price_cp' => 1_000],
    ['kind' => 'shield', 'name' => 'Scudo +1', 'shield_bonus' => 3],

    ['kind' => 'weapon', 'name' => 'Pugnale', 'damage' => '1d4', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Accurata', 'Leggera', 'Lancio (6/18 m)'], 'price_cp' => 200],
    ['kind' => 'weapon', 'name' => 'Spada Corta', 'damage' => '1d6', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Accurata', 'Leggera'], 'price_cp' => 1_000],
    ['kind' => 'weapon', 'name' => 'Spada Lunga', 'damage' => '1d8', 'damage_type' => 'slashing', 'ability' => 'str', 'properties' => ['Versatile (1d10)'], 'price_cp' => 1_500],
    ['kind' => 'weapon', 'name' => 'Spadone', 'damage' => '2d6', 'damage_type' => 'slashing', 'ability' => 'str', 'properties' => ['Pesante', 'A due mani'], 'price_cp' => 5_000],
    ['kind' => 'weapon', 'name' => 'Ascia da Battaglia', 'damage' => '1d8', 'damage_type' => 'slashing', 'ability' => 'str', 'properties' => ['Versatile (1d10)'], 'price_cp' => 1_000],
    ['kind' => 'weapon', 'name' => 'Ascia Bipenne', 'damage' => '1d12', 'damage_type' => 'slashing', 'ability' => 'str', 'properties' => ['Pesante', 'A due mani'], 'price_cp' => 3_000],
    ['kind' => 'weapon', 'name' => 'Mazza', 'damage' => '1d6', 'damage_type' => 'bludgeoning', 'ability' => 'str', 'price_cp' => 500],
    ['kind' => 'weapon', 'name' => 'Martello da Guerra', 'damage' => '1d8', 'damage_type' => 'bludgeoning', 'ability' => 'str', 'properties' => ['Versatile (1d10)'], 'price_cp' => 1_500],
    ['kind' => 'weapon', 'name' => 'Maglio', 'damage' => '2d6', 'damage_type' => 'bludgeoning', 'ability' => 'str', 'properties' => ['Pesante', 'A due mani'], 'price_cp' => 1_000],
    ['kind' => 'weapon', 'name' => 'Lancia', 'damage' => '1d6', 'damage_type' => 'piercing', 'ability' => 'str', 'properties' => ['Lancio (6/18 m)', 'Versatile (1d8)'], 'price_cp' => 100],
    ['kind' => 'weapon', 'name' => 'Alabarda', 'damage' => '1d10', 'damage_type' => 'slashing', 'ability' => 'str', 'properties' => ['Pesante', 'Portata', 'A due mani'], 'price_cp' => 2_000],
    ['kind' => 'weapon', 'name' => 'Arco Corto', 'damage' => '1d6', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Munizioni (24/96 m)', 'A due mani'], 'price_cp' => 2_500],
    ['kind' => 'weapon', 'name' => 'Arco Lungo', 'damage' => '1d8', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Munizioni (45/180 m)', 'Pesante', 'A due mani'], 'price_cp' => 5_000],
    ['kind' => 'weapon', 'name' => 'Balestra Leggera', 'damage' => '1d8', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Munizioni (24/96 m)', 'Ricarica', 'A due mani'], 'price_cp' => 2_500],
    ['kind' => 'weapon', 'name' => 'Balestra Pesante', 'damage' => '1d10', 'damage_type' => 'piercing', 'ability' => 'dex', 'properties' => ['Munizioni (30/120 m)', 'Pesante', 'Ricarica', 'A due mani'], 'price_cp' => 5_000],
    ['kind' => 'weapon', 'name' => 'Arma +1', 'damage' => '1d8', 'ability' => 'str'],
];
