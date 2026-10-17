<?php

declare(strict_types=1);

use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ArmorClass;

function scoresWithDex(int $dex): AbilityScores
{
    return AbilityScores::fromArray(['dex' => $dex]);
}

describe('classe armatura', function () {
    it('senza armatura è 10 + Destrezza', function () {
        expect(ArmorClass::compute(scoresWithDex(16)))->toBe(13);
    });

    it('con armatura leggera somma tutta la Destrezza', function () {

        expect(ArmorClass::compute(scoresWithDex(18), 'Cuoio Borchiato'))->toBe(16);
    });

    it('con armatura media limita la Destrezza a +2', function () {

        expect(ArmorClass::compute(scoresWithDex(18), 'Mezza Armatura'))->toBe(17)
            ->and(ArmorClass::compute(scoresWithDex(14), 'Mezza Armatura'))->toBe(17);
    });

    it('con armatura media sotto il tetto usa la Destrezza effettiva', function () {

        expect(ArmorClass::compute(scoresWithDex(12), 'Mezza Armatura'))->toBe(16);
    });

    it('con armatura pesante ignora del tutto la Destrezza', function () {
        expect(ArmorClass::compute(scoresWithDex(18), 'Armatura a Piastre'))->toBe(18)
            ->and(ArmorClass::compute(scoresWithDex(6), 'Armatura a Piastre'))->toBe(18);
    });

    it('con armatura pesante non penalizza la Destrezza negativa', function () {
        expect(ArmorClass::compute(scoresWithDex(8), 'Cotta di Maglia'))->toBe(16);
    });

    it('somma lo scudo', function () {
        expect(ArmorClass::compute(scoresWithDex(10), 'Cotta di Maglia', 'Scudo'))->toBe(18)
            ->and(ArmorClass::compute(scoresWithDex(10), 'Cotta di Maglia', 'Scudo +1'))->toBe(19);
    });

    it('tratta un nome di armatura sconosciuto come nessuna armatura', function () {

        expect(ArmorClass::compute(scoresWithDex(16), 'Armatura di Cartone'))->toBe(13);
    });
});

describe('iniziativa', function () {
    it('è il modificatore di Destrezza efficace', function () {
        expect(ArmorClass::initiative(scoresWithDex(18)))->toBe(4)
            ->and(ArmorClass::initiative(scoresWithDex(7)))->toBe(-2);
    });
});
