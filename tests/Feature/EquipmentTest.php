<?php

declare(strict_types=1);

use App\Actions\Characters\EquipItem;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\CharacterItem;


function inBackpack(Character $character, string $name, int $qty = 1): CharacterItem
{
    return $character->items()->create(['name' => $name, 'qty' => $qty]);
}

describe('indossare', function () {
    it('deduce lo slot dal catalogo', function () {
        $character = Character::factory()->create();

        $armor = app(EquipItem::class)->equip(inBackpack($character, 'Cotta di Maglia'));
        $shield = app(EquipItem::class)->equip(inBackpack($character, 'Scudo'));
        $sword = app(EquipItem::class)->equip(inBackpack($character, 'Spada Lunga'));

        expect($armor->equipped_slot)->toBe(EquipmentSlot::Armor)
            ->and($shield->equipped_slot)->toBe(EquipmentSlot::Shield)
            ->and($sword->equipped_slot)->toBe(EquipmentSlot::Weapon);
    });

    it('rifiuta quello che non si indossa', function () {
        $character = Character::factory()->create();

        expect(fn () => app(EquipItem::class)->equip(inBackpack($character, 'Corda di Canapa')))
            ->toThrow(RuntimeException::class);
    });

    it('rifiuta uno slot sbagliato', function () {
        $character = Character::factory()->create();

        expect(fn () => app(EquipItem::class)->equip(
            inBackpack($character, 'Spada Lunga'), EquipmentSlot::Armor
        ))->toThrow(RuntimeException::class);
    });

    it('gli oggetti magici non si indossano: ci si va in sintonia', function () {
        $character = Character::factory()->create();
        $amulet = inBackpack($character, 'Amuleto della Salute');
// Gli oggetti magici fuori dal catalogo dell'equipaggiamento passano dalla sintonia, non da uno slot generico.
        expect(fn () => app(EquipItem::class)->equip($amulet))
            ->toThrow(RuntimeException::class, 'non è qualcosa che si indossa');
    });
});

describe('uno slot, un oggetto', function () {
    it('il pezzo precedente torna nello zaino', function () {
        $character = Character::factory()->create();
        $leather = inBackpack($character, 'Armatura di Cuoio');
        $chain = inBackpack($character, 'Cotta di Maglia');

        app(EquipItem::class)->equip($leather);
        app(EquipItem::class)->equip($chain);

        expect($chain->fresh()->equipped_slot)->toBe(EquipmentSlot::Armor)
            ->and($leather->fresh()->equipped_slot)->toBeNull()
            ->and($leather->fresh()->qty)->toBe(1);
    });

    it('la Classe Armatura segue quello che si indossa', function () {
        $character = Character::factory()->create(['dex' => 14]);
        $chain = inBackpack($character, 'Cotta di Maglia');

        expect($character->armorClass())->toBe(12);

        app(EquipItem::class)->equip($chain);

        expect($character->fresh()->armorClass())->toBe(16);

        app(EquipItem::class)->unequip($chain->fresh());

        expect($character->fresh()->armorClass())->toBe(12);
    });
});

describe('le pile', function () {
    it('da tre pugnali se ne impugna uno', function () {
        $character = Character::factory()->create();
        $stack = inBackpack($character, 'Pugnale', qty: 3);

        $equipped = app(EquipItem::class)->equip($stack);

        expect($equipped->qty)->toBe(1)
            ->and($equipped->equipped_slot)->toBe(EquipmentSlot::Weapon)
            ->and($stack->fresh()->qty)->toBe(2)
            ->and($stack->fresh()->equipped_slot)->toBeNull();
    });

    it('riponendolo si riaccorpa alla pila invece di restare a parte', function () {
        $character = Character::factory()->create();
        $stack = inBackpack($character, 'Pugnale', qty: 3);

        $equipped = app(EquipItem::class)->equip($stack);
        app(EquipItem::class)->unequip($equipped->fresh());

        $rows = $character->items()->where('name', 'Pugnale')->get();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()->qty)->toBe(3);
    });

    it('senza pila a cui tornare resta una riga sola', function () {
        $character = Character::factory()->create();
        $sword = inBackpack($character, 'Spada Lunga');

        app(EquipItem::class)->equip($sword);
        app(EquipItem::class)->unequip($sword->fresh());

        expect($character->items()->where('name', 'Spada Lunga')->count())->toBe(1);
    });
});

describe('riporre', function () {
    it('su un oggetto già riposto non fa niente', function () {
        $character = Character::factory()->create();
        $sword = inBackpack($character, 'Spada Lunga');

        expect(app(EquipItem::class)->unequip($sword)->equipped_slot)->toBeNull();
    });
});

describe('chi può', function () {
    it('il proprietario di un personaggio vivo', function () {
        $character = Character::factory()->create();

        expect($character->user->can('manageEquipment', $character))->toBeTrue();
    });

    it('non un altro giocatore', function () {
        $character = Character::factory()->create();

        expect(App\Models\User::factory()->player()->create()->can('manageEquipment', $character))->toBeFalse();
    });

    it('ma anche un DM o un admin, in caso di necessità', function () {
        $character = Character::factory()->create();

        expect(App\Models\User::factory()->dm()->create()->can('manageEquipment', $character))->toBeTrue()
            ->and(App\Models\User::factory()->admin()->create()->can('manageEquipment', $character))->toBeTrue();
    });

    it('non su un personaggio caduto', function () {
        $character = Character::factory()->fallen()->create();

        expect($character->user->can('manageEquipment', $character))->toBeFalse();
    });
});
