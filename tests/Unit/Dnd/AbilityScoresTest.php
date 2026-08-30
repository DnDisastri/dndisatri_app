<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ItemEffect;
use App\Domain\Dnd\ItemEffectMode;

describe('modificatore di caratteristica', function () {
    it('è floor((punteggio - 10) / 2)', function (int $score, int $expected) {
        expect(Ability::modifierFor($score))->toBe($expected);
    })->with([
        [1, -5], [3, -4], [7, -2], [8, -1], [9, -1],
        [10, 0], [11, 0], [12, 1], [15, 2], [16, 3], [20, 5], [24, 7],
    ]);

    it('arrotonda verso il basso anche sotto 10', function () {

        expect(Ability::modifierFor(7))->toBe(-2);
    });

    it('formatta il modificatore col segno', function () {
        expect(Ability::format(3))->toBe('+3')
            ->and(Ability::format(0))->toBe('+0')
            ->and(Ability::format(-2))->toBe('-2');
    });
});

describe('punteggi mancanti', function () {
    it('valgono 10, cioè modificatore zero', function () {
        $scores = AbilityScores::fromArray(['str' => 16]);

        expect($scores->score(Ability::Str))->toBe(16)
            ->and($scores->score(Ability::Cha))->toBe(10)
            ->and($scores->modifier(Ability::Cha))->toBe(0);
    });
});

describe('effetti degli oggetti magici', function () {
    it('con modo "set" porta il punteggio al valore indicato', function () {
        $base = AbilityScores::fromArray(['str' => 12]);
        $belt = new ItemEffect(Ability::Str, ItemEffectMode::Set, 21, 'Cintura di Forza del Gigante');

        expect($base->withEffects([$belt])->score(Ability::Str))->toBe(21);
    });

    it('con modo "set" non peggiora chi è già più forte', function () {
        $base = AbilityScores::fromArray(['str' => 23]);
        $belt = new ItemEffect(Ability::Str, ItemEffectMode::Set, 21);

        expect($base->withEffects([$belt])->score(Ability::Str))->toBe(23);
    });

    it('con modo "bonus" somma, anche in negativo', function () {
        $base = AbilityScores::fromArray(['dex' => 14, 'wis' => 12]);

        $effective = $base->withEffects([
            new ItemEffect(Ability::Dex, ItemEffectMode::Bonus, 2),
            new ItemEffect(Ability::Wis, ItemEffectMode::Bonus, -3),
        ]);

        expect($effective->score(Ability::Dex))->toBe(16)
            ->and($effective->score(Ability::Wis))->toBe(9);
    });

    it('applica più effetti nell\'ordine in cui arrivano', function () {
        $base = AbilityScores::fromArray(['str' => 10]);

        $effective = $base->withEffects([
            new ItemEffect(Ability::Str, ItemEffectMode::Bonus, 4), 
            new ItemEffect(Ability::Str, ItemEffectMode::Set, 19),  
            new ItemEffect(Ability::Str, ItemEffectMode::Bonus, 1),  
        ]);

        expect($effective->score(Ability::Str))->toBe(20);
    });

    it('non tocca i punteggi base: togliendo l\'oggetto torna tutto indietro', function () {
        $base = AbilityScores::fromArray(['str' => 12]);

        $base->withEffects([new ItemEffect(Ability::Str, ItemEffectMode::Set, 21)]);

        expect($base->score(Ability::Str))->toBe(12);
    });

    it('si costruisce dai dati salvati', function () {
        $effect = ItemEffect::fromArray([
            'ability' => 'con',
            'mode' => 'bonus',
            'value' => 2,
            'name' => 'Amuleto della Salute',
        ]);

        expect($effect->ability)->toBe(Ability::Con)
            ->and($effect->mode)->toBe(ItemEffectMode::Bonus)
            ->and($effect->applyTo(14))->toBe(16);
    });
});
