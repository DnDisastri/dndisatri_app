<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\AttuneItem;
use App\Actions\Characters\GrantEffect;
use App\Actions\Characters\ProposeChange;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Models\Character;
use App\Models\User;

// Gli effetti legati a un oggetto contano solo finché l'oggetto è posseduto e in sintonia.
function magicItem(Character $character, string $name, Ability $ability, int $value): void
{
    $item = $character->addToInventory(name: $name, category: 'Oggetti magici');

    $character->itemEffects()->create([
        'character_item_id' => $item->getKey(),
        'name' => $name,
        'ability' => $ability->value,
        'mode' => ItemEffectMode::Bonus->value,
        'value' => $value,
    ]);

    app(AttuneItem::class)->attune($item);
}

describe('l\'effetto vale solo in sintonia', function () {
    it('acceso quando lo si porta', function () {
        $character = Character::factory()->create(['str' => 14]);
        magicItem($character, 'Cintura del Gigante', Ability::Str, 4);

        expect($character->fresh()->effectiveScores()->score(Ability::Str))->toBe(18);
    });

    it('spento quando si toglie la sintonia', function () {
        $character = Character::factory()->create(['str' => 14]);
        magicItem($character, 'Cintura del Gigante', Ability::Str, 4);

        $belt = $character->fresh()->items->firstWhere('name', 'Cintura del Gigante');
        app(AttuneItem::class)->release($belt);

        expect($character->fresh()->effectiveScores()->score(Ability::Str))->toBe(14);
    });

    it('e sparisce del tutto vendendo l\'oggetto', function () {
        $character = Character::factory()->create(['str' => 14]);
        magicItem($character, 'Cintura del Gigante', Ability::Str, 4);

        $character->fresh()->removeFromInventory('Cintura del Gigante');

        expect($character->fresh()->effectiveScores()->score(Ability::Str))->toBe(14)
            ->and($character->fresh()->itemEffects)->toHaveCount(0);
    });

    it('anche i punti ferita massimi tornano indietro', function () {
        $character = Character::factory()->create(['con' => 12, 'level' => 5, 'hp_max' => 40]);
        magicItem($character, 'Amuleto della Salute', Ability::Con, 4);

        expect($character->fresh()->effectiveHpMax())->toBe(50);

        $character->fresh()->removeFromInventory('Amuleto della Salute');

        expect($character->fresh()->effectiveHpMax())->toBe(40);
    });
});

describe('la sintonia si tiene con tre oggetti', function () {
    it('il quarto viene rifiutato', function () {
        $character = Character::factory()->create();

        foreach (['Anello', 'Amuleto', 'Mantello'] as $name) {
            app(AttuneItem::class)->attune($character->addToInventory($name));
        }

        expect($character->fresh()->attunementSlotsLeft())->toBe(0);

        expect(fn () => app(AttuneItem::class)->attune($character->addToInventory('Stivali')))
            ->toThrow(RuntimeException::class, 'al massimo 3');
    });

    it('togliendone uno si libera il posto', function () {
        $character = Character::factory()->create();

        $items = collect(['Anello', 'Amuleto', 'Mantello'])
            ->map(fn ($n) => app(AttuneItem::class)->attune($character->addToInventory($n)));

        app(AttuneItem::class)->release($items->first());

        $stivali = $character->addToInventory('Stivali');
        app(AttuneItem::class)->attune($stivali);

        expect($stivali->fresh()->attuned)->toBeTrue()
            ->and($character->fresh()->attunedItems())->toHaveCount(3);
    });

    it('sintonizzare due volte lo stesso oggetto non consuma due posti', function () {
        $character = Character::factory()->create();
        $ring = $character->addToInventory('Anello');

        app(AttuneItem::class)->attune($ring);
        app(AttuneItem::class)->attune($ring->fresh());

        expect($character->fresh()->attunementSlotsLeft())->toBe(2);
    });
});

describe('l\'approvazione di un oggetto magico', function () {
    it('crea l\'oggetto, lo lega all\'effetto e lo sintonizza', function () {
        $character = Character::factory()->create(['str' => 14]);

        $change = app(ProposeChange::class)->itemEffect(
            $character, $character->user, 'Cintura del Gigante',
            Ability::Str, ItemEffectMode::Set, 19,
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $fresh = $character->fresh();

        expect($fresh->ownsItem('Cintura del Gigante'))->toBeTrue()
            ->and($fresh->itemEffects->first()->character_item_id)->not->toBeNull()
            ->and($fresh->effectiveScores()->score(Ability::Str))->toBe(19);
    });

    it('non crea un doppione se l\'oggetto c\'è già', function () {
        $character = Character::factory()->create();
        $character->addToInventory('Anello di Protezione');

        $change = app(ProposeChange::class)->itemEffect(
            $character, $character->user, 'Anello di Protezione',
            Ability::Dex, ItemEffectMode::Bonus, 1,
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->items()->where('name', 'Anello di Protezione')->count())->toBe(1);
    });

    // Se i tre slot di sintonia sono occupati, l'oggetto approvato entra comunque in inventario ma il suo effetto resta inattivo.
    it('con tre già in sintonia l\'oggetto arriva spento', function () {
        $character = Character::factory()->create(['str' => 14]);

        foreach (['Anello', 'Amuleto', 'Mantello'] as $name) {
            app(AttuneItem::class)->attune($character->addToInventory($name));
        }

        $change = app(ProposeChange::class)->itemEffect(
            $character->fresh(), $character->user, 'Cintura del Gigante',
            Ability::Str, ItemEffectMode::Bonus, 4,
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $fresh = $character->fresh();

        expect($fresh->ownsItem('Cintura del Gigante'))->toBeTrue()
        ->and($fresh->effectiveScores()->score(Ability::Str))->toBe(14);
    });
});

describe('benedizioni e maledizioni', function () {
    it('valgono senza nessun oggetto', function () {
        $character = Character::factory()->create(['cha' => 10]);

        app(GrantEffect::class)->grant(
            $character, User::factory()->dm()->create(),
            'Benedizione della Fortuna', Ability::Cha, ItemEffectMode::Bonus, 2,
        );

        expect($character->fresh()->effectiveScores()->score(Ability::Cha))->toBe(12);
    });

    it('non occupano un posto di sintonia', function () {
        $character = Character::factory()->create();

        app(GrantEffect::class)->grant(
            $character, User::factory()->dm()->create(),
            'Maledizione', Ability::Str, ItemEffectMode::Bonus, -2,
        );

        expect($character->fresh()->attunementSlotsLeft())->toBe(3);
    });

    it('le toglie un DM', function () {
        $character = Character::factory()->create(['cha' => 10]);
        $dm = User::factory()->dm()->create();

        $effect = app(GrantEffect::class)->grant(
            $character, $dm, 'Benedizione', Ability::Cha, ItemEffectMode::Bonus, 2,
        );

        app(GrantEffect::class)->revoke($effect, $dm);

        expect($character->fresh()->effectiveScores()->score(Ability::Cha))->toBe(10);
    });

    it('ma non un giocatore', function () {
        $character = Character::factory()->create();

        expect(fn () => app(GrantEffect::class)->grant(
            $character, User::factory()->player()->create(),
            'Auto-benedizione', Ability::Str, ItemEffectMode::Bonus, 5,
        ))->toThrow(RuntimeException::class);
    });
});

describe('chi gestisce la sintonia', function () {
    it('il proprietario, e un DM quando serve', function () {
        $character = Character::factory()->create();

        expect($character->user->can('manageAttunement', $character))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('manageAttunement', $character))->toBeTrue()
            ->and(User::factory()->player()->create()->can('manageAttunement', $character))->toBeFalse();
    });
});
