<?php

declare(strict_types=1);

use App\Models\Character;

function withClasses(Character $character, array $levels): Character
{
    $first = true;

    foreach ($levels as $class => $level) {
        $character->classes()->create([
            'class' => $class,
            'level' => $level,
            'is_primary' => $first,
        ]);
        $first = false;
    }

    return $character->fresh();
}

describe('le classi', function () {
    it('un personaggio senza righe ricade sulla copia della scheda', function () {
// Mantiene compatibilità con i personaggi monoclasse che non hanno righe nella relazione delle classi.
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 5]);

        expect($character->classLevels())->toBe(['Mago' => 5])
            ->and($character->isMulticlass())->toBeFalse();
    });

    it('con le righe, sono quelle a comandare', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Guerriero', 'level' => 5]),
            ['Guerriero' => 3, 'Mago' => 2],
        );

        expect($character->classLevels())->toBe(['Guerriero' => 3, 'Mago' => 2])
            ->and($character->isMulticlass())->toBeTrue()
            ->and($character->levelIn('Mago'))->toBe(2)
            ->and($character->levelIn('Chierico'))->toBe(0);
    });

    it('la principale è la prima presa', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Guerriero']),
            ['Guerriero' => 3, 'Mago' => 2],
        );

        expect($character->primaryClass()->class)->toBe('Guerriero')

            ->and($character->classes->first()->class)->toBe('Guerriero');
    });
});

describe('gli slot di un multiclasse', function () {
    it('non sono la somma di quelli delle sue classi', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Chierico', 'level' => 5]),
            ['Chierico' => 3, 'Paladino' => 2],
        );

        expect($character->spellSlots()->slots)->toBe([1 => 4, 2 => 3]);
    });

    it('un monoclasse resta identico a prima', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Mago', 'level' => 5]),
            ['Mago' => 5],
        );

        expect($character->spellSlots()->slots)->toBe([1 => 4, 2 => 3, 3 => 2]);
    });

    it('il Warlock porta una riserva separata', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Warlock', 'level' => 6]),
            ['Warlock' => 3, 'Mago' => 3],
        );

        expect($character->pactSlots()->isPact)->toBeTrue()
            ->and($character->pactSlots()->total())->toBe(2)
            ->and($character->spellSlots()->slots)->toBe([1 => 4, 2 => 2])
            ->and($character->spellSlots()->isPact)->toBeFalse();
    });

    it('chi non lancia non ne ha, da nessuna delle due parti', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Guerriero', 'level' => 5]),
            ['Guerriero' => 3, 'Barbaro' => 2],
        );

        expect($character->spellSlots()->isEmpty())->toBeTrue()
            ->and($character->pactSlots()->isEmpty())->toBeTrue();
    });
});

describe('quello che dipende dalla singola classe', function () {
    it('il dado vita è quello di quella classe, non del personaggio', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Guerriero', 'hit_die' => 10]),
            ['Guerriero' => 3, 'Mago' => 2],
        );

        $dice = $character->classes->pluck('class')->map(
            fn ($c) => $character->classes->firstWhere('class', $c)->hitDie()
        );

        expect($dice->all())->toBe([10, 6]);
    });

    it('la sottoclasse si sceglie al livello di quella classe', function () {
        $character = withClasses(
            Character::factory()->create(['class' => 'Guerriero']),
            ['Guerriero' => 3, 'Mago' => 1],
        );

        $mago = $character->classes->firstWhere('class', 'Mago');

        expect($mago->subclassLevel())->toBe(2)
            ->and($mago->needsSubclass())->toBeFalse();

        $mago->update(['level' => 2]);

        expect($mago->fresh()->needsSubclass())->toBeTrue();
    });
});
