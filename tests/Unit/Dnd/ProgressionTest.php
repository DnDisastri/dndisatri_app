<?php

declare(strict_types=1);

use App\Domain\Dnd\Progression;

describe('bonus di competenza', function () {
    it('vale +2 dal primo al quarto livello', function (int $level) {
        expect(Progression::proficiencyBonus($level))->toBe(2);
    })->with([1, 2, 3, 4]);

    it('sale di uno ogni quattro livelli', function (int $level, int $expected) {
        expect(Progression::proficiencyBonus($level))->toBe($expected);
    })->with([
        [5, 3], [8, 3],
        [9, 4], [12, 4],
        [13, 5], [16, 5],
        [17, 6], [20, 6],
    ]);
});

describe('livelli di ASI', function () {
    it('sono il 4, 8, 12, 16 e 19', function (int $level) {
        expect(Progression::isAsiLevel($level))->toBeTrue();
    })->with([4, 8, 12, 16, 19]);

    it('non sono gli altri', function (int $level) {
        expect(Progression::isAsiLevel($level))->toBeFalse();
    })->with([1, 3, 5, 7, 11, 15, 17, 18, 20]);
});

describe('livello di scelta della sottoclasse', function () {
    it('è il primo per Chierico, Stregone e Warlock', function (string $class) {
        expect(Progression::subclassLevel($class))->toBe(1);
    })->with(['Chierico', 'Stregone', 'Warlock']);

    it('è il secondo per Druido e Mago', function (string $class) {
        expect(Progression::subclassLevel($class))->toBe(2);
    })->with(['Druido', 'Mago']);

    it('è il terzo per tutte le altre', function (string $class) {
        expect(Progression::subclassLevel($class))->toBe(3);
    })->with(['Barbaro', 'Bardo', 'Guerriero', 'Ladro', 'Monaco', 'Paladino', 'Ranger']);
});

describe('quota di Esperto', function () {
    it('per il Ladro è 2 dal primo livello e 4 dal sesto', function () {
        expect(Progression::expertiseCount('Ladro', 1))->toBe(2)
            ->and(Progression::expertiseCount('Ladro', 5))->toBe(2)
            ->and(Progression::expertiseCount('Ladro', 6))->toBe(4)
            ->and(Progression::expertiseCount('Ladro', 20))->toBe(4);
    });

    it('per il Bardo è 0 fino al secondo, 2 dal terzo, 4 dal decimo', function () {
        expect(Progression::expertiseCount('Bardo', 1))->toBe(0)
            ->and(Progression::expertiseCount('Bardo', 2))->toBe(0)
            ->and(Progression::expertiseCount('Bardo', 3))->toBe(2)
            ->and(Progression::expertiseCount('Bardo', 9))->toBe(2)
            ->and(Progression::expertiseCount('Bardo', 10))->toBe(4);
    });

    it('è zero per tutte le altre classi', function (string $class) {
        expect(Progression::expertiseCount($class, 20))->toBe(0);
    })->with(['Barbaro', 'Chierico', 'Druido', 'Guerriero', 'Mago', 'Monaco', 'Paladino', 'Ranger', 'Stregone', 'Warlock']);
});
