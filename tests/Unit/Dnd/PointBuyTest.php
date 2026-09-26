<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\PointBuy;

describe('costo dei punteggi', function () {
    it('sale di uno fino a 13, poi di due', function (int $score, int $cost) {
        expect(PointBuy::costOf($score))->toBe($cost);
    })->with([
        [8, 0], [9, 1], [10, 2], [11, 3], [12, 4], [13, 5], [14, 7], [15, 9],
    ]);

    it('non ammette punteggi fuori dai limiti', function () {

        expect(PointBuy::costOf(7))->toBeNull()
            ->and(PointBuy::costOf(16))->toBeNull();
    });
});

describe('il budget', function () {
    it('parte da 27 con tutti i punteggi a 8', function () {
        expect(PointBuy::remaining(PointBuy::starting()))->toBe(27)
            ->and(PointBuy::spent(PointBuy::starting()))->toBe(0);
    });

    it('si esaurisce con una distribuzione classica', function () {

        $scores = ['str' => 15, 'dex' => 15, 'con' => 15, 'int' => 8, 'wis' => 8, 'cha' => 8];

        expect(PointBuy::spent($scores))->toBe(27)
            ->and(PointBuy::remaining($scores))->toBe(0)
            ->and(PointBuy::isValid($scores))->toBeTrue();
    });

    it('rifiuta una distribuzione che sfora', function () {
        $scores = ['str' => 15, 'dex' => 15, 'con' => 15, 'int' => 12, 'wis' => 8, 'cha' => 8];

        expect(PointBuy::remaining($scores))->toBeLessThan(0)
            ->and(PointBuy::isValid($scores))->toBeFalse();
    });

    it('rifiuta un punteggio non comprabile', function () {
        $scores = ['str' => 18, 'dex' => 8, 'con' => 8, 'int' => 8, 'wis' => 8, 'cha' => 8];

        expect(PointBuy::isValid($scores))->toBeFalse();
    });
});

describe('bonus di specie', function () {
    it('si sommano DOPO l\'acquisto, e possono superare 15', function () {
        $bought = ['str' => 15, 'dex' => 14, 'con' => 13, 'int' => 8, 'wis' => 10, 'cha' => 8];


        $final = PointBuy::withSpecies($bought, 'Mezzorco');

        expect($final['str'])->toBe(17)
            ->and($final['con'])->toBe(14)
            ->and($final['dex'])->toBe(14);
    });

    it('l\'Umano prende +1 dappertutto', function () {
        $final = PointBuy::withSpecies(PointBuy::starting(), 'Umano');

        foreach (Ability::cases() as $ability) {
            expect($final[$ability->value])->toBe(9);
        }
    });

    it('Umano Variante e Mezzelfo hanno due +1 a scelta', function () {
        expect(PointBuy::freeBonusesFor('Umano (Variante)'))->toBe(2)
            ->and(PointBuy::freeBonusesFor('Mezzelfo'))->toBe(2)
            ->and(PointBuy::freeBonusesFor('Nano'))->toBe(0);

        $final = PointBuy::withSpecies(PointBuy::starting(), 'Umano (Variante)', ['str' => 1, 'con' => 1]);

        expect($final['str'])->toBe(9)
            ->and($final['con'])->toBe(9)
            ->and($final['dex'])->toBe(8);
    });
});

describe('quello che concede la classe', function () {
    it('dado vita e tiri salvezza', function () {
        expect(ClassRules::hitDie('Barbaro'))->toBe(12)
            ->and(ClassRules::hitDie('Mago'))->toBe(6)
            ->and(ClassRules::savingThrows('Guerriero'))->toBe(['str', 'con']);
    });

    it('numero ed elenco delle abilità', function () {
        expect(ClassRules::skillCount('Ladro'))->toBe(4)
            ->and(ClassRules::skillCount('Guerriero'))->toBe(2)
            ->and(ClassRules::skillChoices('Chierico'))
            ->toBe(['history', 'insight', 'medicine', 'persuasion', 'religion']);
    });

    it('il Bardo sceglie fra tutte e diciotto', function () {
        expect(ClassRules::skillChoices('Bardo'))->toHaveCount(18);
    });

    it('tutte e dodici le classi sono a catalogo', function () {
        expect(ClassRules::names())->toHaveCount(12)
            ->and(ClassRules::exists('Paladino'))->toBeTrue()
            ->and(ClassRules::exists('Pasticciere'))->toBeFalse();
    });
});

describe('incantesimi al primo livello', function () {
    it('il Mago ne conosce sei, più tre trucchetti', function () {
        $known = ClassRules::spellsKnownAtFirst('Mago');

        expect($known['cantrips'])->toBe(3)
            ->and($known['spells'])->toBe(6);
    });

    it('i preparati si contano come modificatore più livello', function () {

        $scores = AbilityScores::fromArray(['wis' => 16]);

        expect(ClassRules::spellCountAtFirst('Chierico', $scores))->toBe(4);
    });

    it('mai meno di uno, anche con la caratteristica scarsa', function () {
        $scores = AbilityScores::fromArray(['wis' => 8]);

        expect(ClassRules::spellCountAtFirst('Druido', $scores))->toBe(1);
    });

    it('chi non lancia al primo livello non ne sceglie', function () {
        $scores = AbilityScores::fromArray(['cha' => 16]);

        expect(ClassRules::spellCountAtFirst('Paladino', $scores))->toBe(0)
            ->and(ClassRules::spellCountAtFirst('Barbaro', $scores))->toBe(0);
    });

    it('deduce il livello di un incantesimo dalla libreria', function () {
        expect(ClassRules::spellLevel('Dardo di Fuoco'))->toBe(0)
            ->and(ClassRules::spellLevel('Dardo Incantato'))->toBe(1)
            ->and(ClassRules::spellLevel('Palla di Fuoco'))->toBe(3);
    });
});
