<?php

declare(strict_types=1);

use App\Actions\Characters\EquipItem;
use App\Models\Character;


describe('quali armi compaiono', function () {
    it('quelle a catalogo che si possiedono', function () {
        $character = Character::factory()->create(['str' => 16, 'dex' => 10, 'level' => 1]);
        $character->addToInventory('Spada Lunga');
        $character->addToInventory('Corda di Canapa');

        $attacks = $character->fresh()->attacks();

        expect($attacks)->toHaveCount(1)
            ->and($attacks->first()['name'])->toBe('Spada Lunga');
    });

    it('e sparisce quando l\'arma se ne va', function () {
        $character = Character::factory()->create();
        $character->addToInventory('Spada Lunga');

        expect($character->fresh()->attacks())->toHaveCount(1);

        $character->removeFromInventory('Spada Lunga');

        expect($character->fresh()->attacks())->toHaveCount(0);
    });

    it('quella impugnata viene per prima', function () {
        $character = Character::factory()->create();
        $character->addToInventory('Pugnale');
        $dagger = $character->addToInventory('Spada Lunga');

        app(EquipItem::class)->equip($dagger);

        expect($character->fresh()->attacks()->first()['name'])->toBe('Spada Lunga')
            ->and($character->fresh()->attacks()->first()['equipped'])->toBeTrue();
    });
});

describe('i numeri', function () {
    it('bonus di attacco: caratteristica più competenza', function () {
        $character = Character::factory()->create(['str' => 16, 'level' => 1]);
        $character->addToInventory('Spada Lunga');

        $attack = $character->fresh()->attacks()->first();

        expect($attack['attack'])->toBe(5)
            ->and($attack['damage'])->toBe('1d8+3');
    });

    it('le armi da destrezza usano la Destrezza', function () {
        $character = Character::factory()->create(['str' => 8, 'dex' => 18, 'level' => 5]);
        $character->addToInventory('Pugnale');

        $attack = $character->fresh()->attacks()->first();

        expect($attack['attack'])->toBe(7)
            ->and($attack['damage'])->toBe('1d4+4');
    });

    it('con modificatore zero i danni restano il dado nudo', function () {
        $character = Character::factory()->create(['str' => 10, 'level' => 1]);
        $character->addToInventory('Spada Lunga');

        expect($character->fresh()->attacks()->first()['damage'])->toBe('1d8');
    });

    it('un oggetto magico che alza la Forza alza anche l\'attacco', function () {
        $character = Character::factory()->create(['str' => 10, 'level' => 1]);
        $character->addToInventory('Spada Lunga');
        $character->itemEffects()->create([
            'name' => 'Cintura del Gigante', 'ability' => 'str', 'mode' => 'set', 'value' => 18,
        ]);

        expect($character->fresh()->attacks()->first()['attack'])->toBe(6);
    });
});

describe('le correzioni del DM', function () {
    it('una spada +1 aggiunge il bonus ad attacco e danni', function () {
        $character = Character::factory()->create(['str' => 16, 'level' => 1]);
        $character->addToInventory('Spada Lunga');
        $character->weapons()->create([
            'name' => 'Spada Lunga', 'attack_ability' => 'str', 'weapon_bonus' => 1,
        ]);

        $attack = $character->fresh()->attacks()->first();

        expect($attack['attack'])->toBe(6)
            ->and($attack['damage'])->toBe('1d8+4');
    });

    it('fanno comparire anche un\'arma che non è a catalogo', function () {
        $character = Character::factory()->create(['str' => 14, 'level' => 1]);
        $character->addToInventory('Zanna di Drago');
        $character->weapons()->create([
            'name' => 'Zanna di Drago', 'attack_ability' => 'str',
            'weapon_bonus' => 2, 'damage' => '2d6',
        ]);

        $attack = $character->fresh()->attacks()->first();

        expect($attack['name'])->toBe('Zanna di Drago')
            ->and($attack['attack'])->toBe(6)
            ->and($attack['damage'])->toBe('2d6+4');
    });

    it('ma senza l\'oggetto in inventario non si attacca con niente', function () {
        $character = Character::factory()->create();
        $character->weapons()->create(['name' => 'Zanna di Drago', 'damage' => '2d6']);

        expect($character->fresh()->attacks())->toHaveCount(0);
    });

    // Un override del DM può contenere già il modificatore; in quel caso non va aggiunto una seconda volta.
    it('non raddoppia il modificatore se il DM l\'ha già scritto', function () {
        $character = Character::factory()->create(['str' => 16, 'level' => 1]);
        $character->addToInventory('Zanna di Drago');
        $character->weapons()->create([
            'name' => 'Zanna di Drago', 'attack_ability' => 'str', 'damage' => '1d4+3',
        ]);

        expect($character->fresh()->attacks()->first()['damage'])->toBe('1d4+3');
    });

    it('ma a soli dadi lo aggiunge come sempre', function () {
        $character = Character::factory()->create(['str' => 16, 'level' => 1]);
        $character->addToInventory('Zanna di Drago');
        $character->weapons()->create([
            'name' => 'Zanna di Drago', 'attack_ability' => 'str', 'damage' => '1d4',
        ]);

        expect($character->fresh()->attacks()->first()['damage'])->toBe('1d4+3');
    });
});
