<?php

declare(strict_types=1);

use App\Models\CharacterSpell;

// Il parser viene verificato sulle descrizioni reali della libreria, inclusi casi con più numeri potenzialmente ambigui.
function spell(string $name, int $level, string $description): CharacterSpell
{
    return new CharacterSpell(['name' => $name, 'level' => $level, 'description' => $description]);
}

it('legge un trucchetto a colpo con scaling', function () {
    $s = spell('Dardo di Fuoco', 0,
        'Trucchetto di Evocazione. Azione, gittata 36 m. Tiro per colpire a distanza; 1d10 danni da fuoco (2d10 al 5°, 3d10 all\'11°, 4d10 al 17°). Può incendiare oggetti.');

    expect($s->rollKind())->toBe('attacco');
    expect($s->castingTime())->toBe('Azione');
    expect($s->range())->toBe('36 m');
    expect($s->damage(1))->toBe('1d10 fuoco');   
    expect($s->damage(5))->toBe('2d10 fuoco');  
    expect($s->damage(11))->toBe('3d10 fuoco');
    expect($s->saveAbility())->toBeNull();
});

it('legge un incantesimo a tiro salvezza', function () {
    $s = spell('Onda Tonante', 1,
        'Invocazione, liv. 1. Azione, cubo di 4,5 m. TS su Costituzione; 2d8 danni da tuono e spinta di 3 m.');

    expect($s->rollKind())->toBe('cd');
    expect($s->castingTime())->toBe('Azione');
    // Non deve confondere la gittata dell'incantesimo con i 3 m della spinta.
    expect($s->range())->toBe('Cubo 4,5 m');
    expect($s->saveAbility())->toBe('COS');
    expect($s->damage())->toBe('2d8 tuono');
});

it('legge i tre raggi di Raggio Rovente', function () {
    $s = spell('Raggio Rovente', 2,
        'Evocazione, liv. 2. Azione, 36 m. Tre raggi, attacco a distanza ciascuno; 2d6 danni da fuoco per raggio.');

    expect($s->rollKind())->toBe('attacco');
    expect($s->range())->toBe('36 m');
    expect($s->damage())->toBe('2d6 fuoco');
});

it('riconosce azione bonus, reazione e tocco', function () {
    $bonus = spell('Parola Guaritrice', 1, 'Invocazione, liv. 1. Azione bonus, 18 m. Cura 1d4 + modificatore. +1d4 per slot superiore.');
    expect($bonus->castingTime())->toBe('Azione bonus');
    expect($bonus->range())->toBe('18 m');
    expect($bonus->scalesUp())->toBeTrue();

    $reaz = spell('Scudo', 1, 'Abiurazione, liv. 1. Reazione. +5 alla CA fino al tuo prossimo turno; annulla Dardo Incantato.');
    expect($reaz->castingTime())->toBe('Reazione');

    $tocco = spell('Armatura Magica', 1, 'Abiurazione, liv. 1. Tocco. La CA base del bersaglio diventa 13 + mod. Destrezza per 8 ore.');
    expect($tocco->range())->toBe('Tocco');
    expect($tocco->rollKind())->toBeNull();
    expect($tocco->scalesUp())->toBeFalse();
});

it('sull\'ignoto non inventa: gittata e danni restano vuoti', function () {
    $s = spell('Legame Strano', 1, 'Una cosa che ho scritto io senza formato.');

    expect($s->range())->toBeNull();
    expect($s->damage())->toBeNull();
    expect($s->saveAbility())->toBeNull();
    // Se il formato non specifica il tempo di lancio, il fallback previsto è "Azione".
    expect($s->castingTime())->toBe('Azione');
});
