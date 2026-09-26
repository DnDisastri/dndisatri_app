<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Livewire\HitPointTracker;
use App\Livewire\InventoryManager;
use App\Models\Character;
use App\Models\User;
use Livewire\Livewire;


describe('i punti ferita', function () {
    it('il proprietario segna danni e cure', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['hp_max' => 30, 'hp_current' => 30]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('amount', 7)
            ->call('damage');

        expect($character->fresh()->hp_current)->toBe(23);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('amount', 3)
            ->call('heal');

        expect($character->fresh()->hp_current)->toBe(26);
    });

    it('e i temporanei', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('tempAmount', 5)
            ->call('aggiungiTemporanei');

        expect($character->fresh()->hp_temp)->toBe(5);
    });

    it('un numero a zero viene rifiutato senza rompere niente', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['hp_current' => 20]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('amount', 0)
            ->call('damage')
            ->assertHasErrors('pf');

        expect($character->fresh()->hp_current)->toBe(20);
    });

    it('un altro giocatore non ci arriva', function () {
        $character = Character::factory()->create(['hp_current' => 20]);

        $this->actingAs(User::factory()->player()->create());

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('damage')
            ->assertForbidden();
    });

    it('un DM sì, perché a volte serve', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => 30]);

        $this->actingAs(User::factory()->dm()->create());

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('amount', 5)
            ->call('damage');

        expect($character->fresh()->hp_current)->toBe(25);
    });
});

describe('l\'equipaggiamento', function () {
    it('si indossa e si ripone, e la Classe Armatura segue', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['dex' => 14]);
        $armor = $character->items()->create(['name' => 'Cotta di Maglia', 'qty' => 1]);

        $this->actingAs($player);

        Livewire::test(InventoryManager::class, ['character' => $character])
            ->call('equip', $armor->id);

        expect($character->fresh()->armorClass())->toBe(16);

        Livewire::test(InventoryManager::class, ['character' => $character])
            ->call('unequip', $armor->id);

        expect($character->fresh()->armorClass())->toBe(12);
    });

    it('non si tocca la roba di un altro', function () {
        $player = User::factory()->player()->create();
        $mio = Character::factory()->ownedBy($player)->create();
        $altrui = Character::factory()->create();
        $suo = $altrui->items()->create(['name' => 'Cotta di Maglia', 'qty' => 1]);

        $this->actingAs($player);
// L'ID inviato dal browser non basta: l'oggetto deve appartenere al personaggio autorizzato.
        expect(fn () => Livewire::test(InventoryManager::class, ['character' => $mio])
            ->call('equip', $suo->id)
        )->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

        expect($suo->fresh()->equipped_slot)->toBeNull();
    });
});

describe('la sintonia', function () {
    it('accende e spegne l\'effetto', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['str' => 14]);

        $belt = $character->items()->create(['name' => 'Cintura del Gigante', 'qty' => 1]);
        $character->itemEffects()->create([
            'character_item_id' => $belt->id,
            'name' => 'Cintura del Gigante',
            'ability' => Ability::Str->value,
            'mode' => ItemEffectMode::Bonus->value,
            'value' => 4,
        ]);

        $this->actingAs($player);

        Livewire::test(InventoryManager::class, ['character' => $character])
            ->call('attune', $belt->id);

        expect($character->fresh()->effectiveScores()->score(Ability::Str))->toBe(18);

        Livewire::test(InventoryManager::class, ['character' => $character])
            ->call('release', $belt->id);

        expect($character->fresh()->effectiveScores()->score(Ability::Str))->toBe(14);
    });

    it('il quarto oggetto avvisa invece di esplodere', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();

        $this->actingAs($player);

        foreach (['Anello', 'Amuleto', 'Mantello'] as $name) {
            $item = $character->items()->create(['name' => $name, 'qty' => 1]);
            Livewire::test(InventoryManager::class, ['character' => $character])->call('attune', $item->id);
        }

        $quarto = $character->items()->create(['name' => 'Stivali', 'qty' => 1]);

        Livewire::test(InventoryManager::class, ['character' => $character])
            ->call('attune', $quarto->id)
            ->assertHasErrors('inventario');

        expect($quarto->fresh()->attuned)->toBeFalse();
    });
});
