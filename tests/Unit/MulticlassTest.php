<?php

declare(strict_types=1);

use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\Multiclass;

// Gli slot multiclasse derivano dal livello da incantatore combinato: non si sommano gli slot delle singole classi.
describe('il livello da incantatore combinato', function () {
    it('conta per intero gli incantatori completi', function () {
        expect(Multiclass::casterLevel(['Mago' => 3, 'Chierico' => 2]))->toBe(5);
    });

    it('conta a metà i mezzi incantatori, arrotondando per difetto', function () {
        expect(Multiclass::casterLevel(['Paladino' => 3]))->toBe(1)
            ->and(Multiclass::casterLevel(['Ranger' => 5]))->toBe(2);
    });

    it('non conta chi non lancia incantesimi', function () {
        expect(Multiclass::casterLevel(['Guerriero' => 5, 'Barbaro' => 3]))->toBe(0);
    });

    it('non conta il Warlock: ha una riserva sua', function () {
        expect(Multiclass::casterLevel(['Warlock' => 5, 'Mago' => 2]))->toBe(2);
    });

    it('il caso del manuale: Chierico 3 / Paladino 2 vale 4', function () {
        expect(Multiclass::casterLevel(['Chierico' => 3, 'Paladino' => 2]))->toBe(4);
    });
});

describe('gli slot', function () {
    it('con una classe sola sono quelli di quella classe', function () {
        $solo = Multiclass::slots(['Mago' => 5]);

        expect($solo->slots)->toBe([1 => 4, 2 => 3, 3 => 2]);
    });

    it('non si sommano fra classi diverse', function () {
        $slots = Multiclass::slots(['Chierico' => 3, 'Paladino' => 2]);

        expect($slots->slots)->toBe([1 => 4, 2 => 3])
            ->and($slots->total())->toBe(7);
    });

    it('un mezzo incantatore da solo al primo livello non ne ha', function () {
        expect(Multiclass::slots(['Paladino' => 1])->isEmpty())->toBeTrue();
    });

    it('chi non lancia non ne ha', function () {
        expect(Multiclass::slots(['Guerriero' => 10])->isEmpty())->toBeTrue();
    });
});

describe('gli slot da patto', function () {
    it('restano separati da quelli normali', function () {
        $levels = ['Warlock' => 3, 'Mago' => 3];

        $pact = Multiclass::pactSlots($levels);
        $normal = Multiclass::slots($levels);

        expect($pact->isPact)->toBeTrue()
            ->and($pact->total())->toBe(2)
            ->and($normal->isPact)->toBeFalse()
            ->and($normal->slots)->toBe([1 => 4, 2 => 2]);
    });

    it('senza Warlock non ce ne sono', function () {
        expect(Multiclass::pactSlots(['Mago' => 5])->isEmpty())->toBeTrue();
    });
});

describe('i prerequisiti', function () {
    $scores = fn (array $values) => AbilityScores::fromArray($values);

    it('servono in entrambe le direzioni', function () use ($scores) {
        $tough = $scores(['str' => 15, 'int' => 10]);

        expect(Multiclass::isAllowed($tough, ['Guerriero'], 'Mago'))->toBeFalse()
            ->and(Multiclass::unmetRequirements($tough, ['Guerriero'], 'Mago'))
            ->toContain('Mago richiede 13 in Intelligenza');
    });

    it('e valgono anche per la classe che si ha già', function () use ($scores) {
        $weak = $scores(['str' => 10, 'dex' => 10, 'int' => 16]);

        expect(Multiclass::unmetRequirements($weak, ['Guerriero'], 'Mago'))
            ->toContain('Guerriero richiede 13 in Forza o Destrezza');
    });

    it('al Guerriero basta una delle due', function () use ($scores) {
        $nimble = $scores(['str' => 8, 'dex' => 14, 'int' => 14]);

        expect(Multiclass::isAllowed($nimble, ['Mago'], 'Guerriero'))->toBeTrue();
    });

    it('al Monaco servono entrambe', function () use ($scores) {
        $half = $scores(['dex' => 14, 'wis' => 10]);

        expect(Multiclass::isAllowed($half, [], 'Monaco'))->toBeFalse();

        $whole = $scores(['dex' => 14, 'wis' => 13]);

        expect(Multiclass::isAllowed($whole, [], 'Monaco'))->toBeTrue();
    });

    it('con i punteggi giusti non manca niente', function () use ($scores) {
        $ready = $scores(['str' => 14, 'cha' => 14]);

        expect(Multiclass::unmetRequirements($ready, ['Paladino'], 'Barbaro'))->toBe([]);
    });
});

describe('le competenze entrando in una classe', function () {
    it('quasi tutte non ne danno', function () {
        expect(Multiclass::skillsOnEntry('Mago')['count'])->toBe(0)
            ->and(Multiclass::skillsOnEntry('Chierico')['count'])->toBe(0);
    });

    it('il Bardo ne dà una qualsiasi', function () {
        $entry = Multiclass::skillsOnEntry('Bardo');

        expect($entry['count'])->toBe(1)
            ->and($entry['from'])->toContain('arcana')
            ->and($entry['from'])->toContain('stealth');
    });

    it('il Ladro una dal suo elenco', function () {
        $entry = Multiclass::skillsOnEntry('Ladro');

        expect($entry['count'])->toBe(1)
            ->and($entry['from'])->toContain('stealth')
            ->and($entry['from'])->not->toContain('arcana');
    });
});
