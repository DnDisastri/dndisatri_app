<?php

declare(strict_types=1);

use App\Actions\Characters\AdjustHitPoints;
use App\Actions\Characters\TakeRest;
use App\Enums\RestType;
use App\Models\Character;
use App\Models\User;

describe('danni', function () {
    it('scalano dai punti ferita', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => 30]);

        app(AdjustHitPoints::class)->damage($character, 7);

        expect($character->fresh()->hp_current)->toBe(23);
    });

    it('consumano prima i temporanei, che sono lì per quello', function () {
        $character = Character::factory()->create([
            'hp_max' => 30, 'hp_current' => 30, 'hp_temp' => 5,
        ]);

        app(AdjustHitPoints::class)->damage($character, 3);

        expect($character->fresh()->hp_temp)->toBe(2)
            ->and($character->fresh()->hp_current)->toBe(30);
    });

    it('sfondano i temporanei e proseguono sui veri', function () {
        $character = Character::factory()->create([
            'hp_max' => 30, 'hp_current' => 30, 'hp_temp' => 5,
        ]);

        app(AdjustHitPoints::class)->damage($character, 8);

        expect($character->fresh()->hp_temp)->toBe(0)
            ->and($character->fresh()->hp_current)->toBe(27);
    });

    it('possono portare sotto zero', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => 4]);

        app(AdjustHitPoints::class)->damage($character, 10);

        expect($character->fresh()->hp_current)->toBe(-6);
    });
});

describe('cure', function () {
    it('non superano il massimo', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => 28]);

        app(AdjustHitPoints::class)->heal($character, 10);

        expect($character->fresh()->hp_current)->toBe(30);
    });

    it('da sotto zero sommano, senza riportare al valore curato', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => -5]);

        app(AdjustHitPoints::class)->heal($character, 3);

        expect($character->fresh()->hp_current)->toBe(-2);
    });

    it('non toccano i temporanei', function () {
        $character = Character::factory()->create([
            'hp_max' => 30, 'hp_current' => 10, 'hp_temp' => 4,
        ]);

        app(AdjustHitPoints::class)->heal($character, 5);

        expect($character->fresh()->hp_temp)->toBe(4);
    });
});

describe('punti ferita temporanei', function () {
    it('non si sommano: vince il più alto', function () {
        $character = Character::factory()->create(['hp_temp' => 8]);

        app(AdjustHitPoints::class)->grantTemporary($character, 5);

        expect($character->fresh()->hp_temp)->toBe(8);

        app(AdjustHitPoints::class)->grantTemporary($character->fresh(), 12);

        expect($character->fresh()->hp_temp)->toBe(12);
    });
});

describe('quantità negative', function () {
    it('non si accettano: c\'è il metodo opposto', function () {
        $character = Character::factory()->create();

        expect(fn () => app(AdjustHitPoints::class)->damage($character, -5))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => app(AdjustHitPoints::class)->heal($character, -5))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('il riposo lungo rimette a nuovo (D8)', function () {
    it('riporta i punti ferita al massimo e azzera i temporanei', function () {
        $character = Character::factory()->create([
            'hp_max' => 30, 'hp_current' => 6, 'hp_temp' => 4,
        ]);

        app(TakeRest::class)->handle($character, RestType::Long);

        expect($character->fresh()->hp_current)->toBe(30)
            ->and($character->fresh()->hp_temp)->toBe(0);
    });

    it('risale anche da sotto zero', function () {
        $character = Character::factory()->create(['hp_max' => 30, 'hp_current' => -8]);

        app(TakeRest::class)->handle($character, RestType::Long);

        expect($character->fresh()->hp_current)->toBe(30);
    });

    // Il massimo effettivo deve includere anche modifiche temporanee alle caratteristiche, come gli effetti degli oggetti magici.
    it('usa il massimo efficace, oggetti magici compresi', function () {
        $character = Character::factory()->create([
            'level' => 3, 'con' => 12, 'hp_max' => 24, 'hp_current' => 5,
        ]);

        $character->itemEffects()->create([
            'name' => 'Amuleto della Salute', 'ability' => 'con', 'mode' => 'set', 'value' => 16,
        ]);

        app(TakeRest::class)->handle($character->fresh(), RestType::Long);

        expect($character->fresh()->hp_current)->toBe(30);
    });

    it('il riposo breve invece non li tocca', function () {
        $character = Character::factory()->create([
            'hp_max' => 30, 'hp_current' => 6, 'hp_temp' => 4,
        ]);

        app(TakeRest::class)->handle($character, RestType::Short);

        expect($character->fresh()->hp_current)->toBe(6)
            ->and($character->fresh()->hp_temp)->toBe(4);
    });
});

describe('chi può', function () {
    it('il proprietario, e un DM quando serve rimettere a posto', function () {
        $character = Character::factory()->create();

        expect($character->user->can('manageHitPoints', $character))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('manageHitPoints', $character))->toBeTrue()
            ->and(User::factory()->player()->create()->can('manageHitPoints', $character))->toBeFalse();
    });

    it('su un caduto nessuno, nemmeno un DM', function () {
        $character = Character::factory()->fallen()->create();

        expect($character->user->can('manageHitPoints', $character))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('manageHitPoints', $character))->toBeFalse();
    });
});
