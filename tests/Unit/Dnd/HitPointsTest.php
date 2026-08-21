<?php

declare(strict_types=1);

use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\HitPoints;

describe('PF guadagnati per livello', function () {
    it('usa il metodo media: dado/2 + 1 + modificatore di Costituzione', function (int $die, int $conMod, int $expected) {
        expect(HitPoints::gainForLevel($die, $conMod))->toBe($expected);
    })->with([
        'd6 con COS +0' => [6, 0, 4],
        'd8 con COS +2' => [8, 2, 7],
        'd10 con COS +3' => [10, 3, 9],
        'd12 con COS +1' => [12, 1, 8],
    ]);

    it('non scende mai sotto 1 PF, nemmeno con Costituzione disastrosa', function () {
        expect(HitPoints::gainForLevel(6, -4))->toBe(1)
            ->and(HitPoints::gainForLevel(6, -10))->toBe(1);
    });
});

describe('aumento retroattivo dei PF', function () {
    it('moltiplica per il livello NUOVO meno uno, non per quello vecchio', function () {
// Al livello 8 il bonus retroattivo si applica ai 7 livelli già posseduti, evitando un off-by-one.
        expect(HitPoints::retroactiveGain(1, newLevel: 8))->toBe(7);
    });

    it('vale anche al quarto livello, il primo ASI', function () {
        expect(HitPoints::retroactiveGain(1, newLevel: 4))->toBe(3);
    });

    it('non dà nulla se l\'ASI non fa scattare il modificatore', function () {
        expect(HitPoints::retroactiveGain(0, newLevel: 8))->toBe(0);
    });

    it('non toglie PF se il modificatore cala', function () {
        expect(HitPoints::retroactiveGain(-2, newLevel: 12))->toBe(0);
    });
});

describe('passaggio di livello completo', function () {
    it('somma il guadagno del livello e il retroattivo, usando il modificatore NUOVO', function () {
        expect(HitPoints::onLevelUp(
            hitDie: 10,
            constitutionModifierBefore: 2,
            constitutionModifierAfter: 3,
            newLevel: 8,
        ))->toBe(16);
    });

    it('senza ASI dà solo il guadagno del livello', function () {
        // Anche se un singolo ASI non produce normalmente questo Δ, la funzione deve gestirlo correttamente in isolamento.
        expect(HitPoints::onLevelUp(
            hitDie: 8,
            constitutionModifierBefore: 2,
            constitutionModifierAfter: 2,
            newLevel: 5,
        ))->toBe(7);
    });

    it('un ASI che alza di due il modificatore raddoppia il retroattivo', function () {

        expect(HitPoints::onLevelUp(
            hitDie: 8,
            constitutionModifierBefore: 1,
            constitutionModifierAfter: 3,
            newLevel: 12,
        ))->toBe(30);
    });
});

describe('PF massimi efficaci con oggetti magici', function () {
    it('sale di (Δ modificatore × livello) quando un oggetto alza la Costituzione', function () {
        $base = AbilityScores::fromArray(['con' => 14]);  
        $effective = AbilityScores::fromArray(['con' => 16]); 

        expect(HitPoints::effectiveMax(40, $base, $effective, level: 5))->toBe(45);
    });

    it('scende quando un oggetto abbassa la Costituzione', function () {
        $base = AbilityScores::fromArray(['con' => 16]);
        $effective = AbilityScores::fromArray(['con' => 12]); 

        expect(HitPoints::effectiveMax(40, $base, $effective, level: 5))->toBe(30);
    });

    it('lascia il massimo invariato se il modificatore non cambia', function () {
        $base = AbilityScores::fromArray(['con' => 14]);
        $effective = AbilityScores::fromArray(['con' => 15]);

        expect(HitPoints::effectiveMax(40, $base, $effective, level: 5))->toBe(40);
    });
});
