<?php

declare(strict_types=1);

use App\Domain\Dnd\Coin;
use App\Domain\Dnd\Coins;

describe('valore e scrittura', function () {
    it('somma le pile in rame', function () {
        expect((new Coins(pp: 1, gp: 2, sp: 3, cp: 4))->value())->toBe(1234);
    });

    it('scompone un valore senza platino', function () {
        expect(Coins::fromValue(1234)->toArray())->toBe(['pp' => 0, 'gp' => 12, 'sp' => 3, 'cp' => 4]);
    });

    it('scrive solo le pile non vuote', function () {
        expect((new Coins(pp: 3, gp: 1200, sp: 5))->format())->toBe('3 mp 1.200 mo 5 ma')
            ->and(Coins::none()->format())->toBe('0 mo')
            ->and(Coins::formatValue(750))->toBe('7 mo 5 ma')
            ->and(Coins::formatValue(-5))->toBe('−5 mr');
    });
});

describe('pagare', function () {
    it('usa le monete esatte quando ci sono', function () {
        $delta = (new Coins(gp: 100))->payment(1500);

        expect($delta->toArray())->toBe(['pp' => 0, 'gp' => -15, 'sp' => 0, 'cp' => 0]);
    });

    it('parte dalla moneta più grande', function () {
        $delta = (new Coins(pp: 2, gp: 30))->payment(2500);

        expect($delta->toArray())->toBe(['pp' => -2, 'gp' => -5, 'sp' => 0, 'cp' => 0]);
    });

    it('spezza una moneta e dà il resto', function () {
        $borsa = new Coins(gp: 10);
        $delta = $borsa->payment(5);

        expect($delta->toArray())->toBe(['pp' => 0, 'gp' => -1, 'sp' => 9, 'cp' => 5])
            ->and($borsa->plus($delta)->value())->toBe(995)
            ->and($borsa->plus($delta)->hasNegative())->toBeFalse();
    });

    it('spezza il platino per pagare il rame', function () {
        $borsa = new Coins(pp: 1);
        $delta = $borsa->payment(3);

        expect($borsa->plus($delta)->toArray())->toBe(['pp' => 0, 'gp' => 9, 'sp' => 9, 'cp' => 7]);
    });

    it('spezza la moneta più piccola che basta', function () {
        $borsa = new Coins(pp: 1, gp: 1);
        $delta = $borsa->payment(50);

        expect($borsa->plus($delta)->toArray())->toBe(['pp' => 1, 'gp' => 0, 'sp' => 5, 'cp' => 0]);
    });

    it('riesce sempre se il valore basta', function (array $piles, int $cp) {
        $borsa = Coins::fromArray($piles);
        $dopo = $borsa->plus($borsa->payment($cp));

        expect($dopo->hasNegative())->toBeFalse()
            ->and($dopo->value())->toBe($borsa->value() - $cp);
    })->with([
        [['pp' => 1], 999],
        [['pp' => 3, 'cp' => 2], 1001],
        [['gp' => 1, 'sp' => 1, 'cp' => 1], 111],
        [['pp' => 1, 'sp' => 3], 37],
        [['sp' => 4, 'cp' => 9], 45],
    ]);

    it('rifiuta se il valore non basta', function () {
        expect(fn () => (new Coins(gp: 1))->payment(101))->toThrow(InvalidArgumentException::class);
    });
});

describe('cambiare', function () {
    it('verso l\'alto, solo esatto', function () {
        $delta = (new Coins(gp: 700))->conversion(Coin::Gold, Coin::Platinum, 700);

        expect($delta->toArray())->toBe(['pp' => 70, 'gp' => -700, 'sp' => 0, 'cp' => 0])
            ->and($delta->value())->toBe(0);
    });

    it('verso il basso', function () {
        $delta = (new Coins(pp: 1))->conversion(Coin::Platinum, Coin::Gold, 1);

        expect($delta->toArray())->toBe(['pp' => -1, 'gp' => 10, 'sp' => 0, 'cp' => 0]);
    });

    it('rifiuta un cambio non esatto', function () {
        expect(fn () => (new Coins(gp: 701))->conversion(Coin::Gold, Coin::Platinum, 701))
            ->toThrow(InvalidArgumentException::class, 'multipli di 10');
    });

    it('rifiuta più monete di quelle che si hanno', function () {
        expect(fn () => (new Coins(gp: 5))->conversion(Coin::Gold, Coin::Silver, 6))
            ->toThrow(InvalidArgumentException::class);
    });
});
