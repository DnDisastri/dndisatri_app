<?php

declare(strict_types=1);

use App\Actions\Characters\PrepareSpells;
use App\Models\Character;
use App\Models\User;


function chierico(array $overrides = []): Character
{
    $character = Character::factory()->create([
        'class' => 'Chierico', 'level' => 3, 'wis' => 16,
        ...$overrides,
    ]);

    $character->classes()->create([
        'class' => $character->class, 'level' => $character->level, 'is_primary' => true,
    ]);

    foreach (['Cura Ferite', 'Benedizione', 'Parola Guaritrice'] as $name) {
        $character->spells()->create(['name' => $name, 'level' => 1]);
    }

    $character->spells()->create(['name' => 'Luce', 'level' => 0]);

    return $character->fresh();
}

describe('chi prepara e chi no', function () {
    it('un Chierico prepara', function () {
        expect(chierico()->preparesSpells())->toBeTrue();
    });

    it('un Mago no: quelli che conosce sono sempre pronti', function () {
        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3]);

        expect($mago->preparesSpells())->toBeFalse()
            ->and($mago->preparationLimit())->toBe(0);
    });

    it('il numero è modificatore più livello nella classe', function () {
        expect(chierico()->preparationLimit())->toBe(6);
    });

    it('mai meno di uno, anche col modificatore negativo', function () {
        expect(chierico(['wis' => 6, 'level' => 1])->preparationLimit())->toBe(1);
    });
});

describe('preparare', function () {
    it('rende utilizzabili quelli scelti e spegne gli altri', function () {
        $character = chierico();

        app(PrepareSpells::class)->handle($character, ['Cura Ferite']);

        $active = $character->fresh()->activeSpells()->pluck('name');

        expect($active)->toContain('Cura Ferite')
            ->and($active)->not->toContain('Benedizione');
    });

    it('i trucchetti restano sempre accesi', function () {
        $character = chierico();

        app(PrepareSpells::class)->handle($character, []);

        expect($character->fresh()->activeSpells()->pluck('name')->all())->toBe(['Luce']);
    });

    it('sostituisce la lista invece di aggiungersi', function () {
        $character = chierico();

        app(PrepareSpells::class)->handle($character, ['Cura Ferite']);
        app(PrepareSpells::class)->handle($character->fresh(), ['Benedizione']);

        $active = $character->fresh()->activeSpells()->pluck('name');

        expect($active)->toContain('Benedizione')
            ->and($active)->not->toContain('Cura Ferite');
    });

    it('non se ne preparano più del consentito', function () {
        $character = chierico(['wis' => 8, 'level' => 1]);

        expect(fn () => app(PrepareSpells::class)->handle(
            $character, ['Cura Ferite', 'Benedizione', 'Parola Guaritrice'],
        ))->toThrow(RuntimeException::class, 'Puoi tenerne pronti 1');
    });

    it('non si prepara quello che non si conosce', function () {
        $character = chierico();

        expect(fn () => app(PrepareSpells::class)->handle($character, ['Palla di Fuoco']))
            ->toThrow(RuntimeException::class, 'Non conosci');
    });

    it('e una classe che non prepara viene rifiutata', function () {
        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3]);
        $mago->spells()->create(['name' => 'Dardo Incantato', 'level' => 1]);

        expect(fn () => app(PrepareSpells::class)->handle($mago, ['Dardo Incantato']))
            ->toThrow(RuntimeException::class, 'non prepara');
    });
});

describe('chi non prepara non perde niente', function () {
    it('i suoi incantesimi restano tutti attivi', function () {
        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3]);
        $mago->spells()->create(['name' => 'Dardo Incantato', 'level' => 1]);
        $mago->spells()->create(['name' => 'Scudo', 'level' => 1]);

        expect($mago->fresh()->activeSpells())->toHaveCount(2);
    });
});

describe('chi può', function () {
    it('il proprietario e un DM, non un altro giocatore', function () {
        $character = chierico();

        expect($character->user->can('managePreparedSpells', $character))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('managePreparedSpells', $character))->toBeTrue()
            ->and(User::factory()->player()->create()->can('managePreparedSpells', $character))->toBeFalse();
    });
});
