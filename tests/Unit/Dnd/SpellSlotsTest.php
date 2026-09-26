<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\SpellSlots;

describe('tipo di incantatore', function () {
    it('deriva dalla classe', function (string $class, CasterType $expected) {
        expect(CasterType::for($class))->toBe($expected);
    })->with([
        'Mago' => ['Mago', CasterType::Full],
        'Bardo' => ['Bardo', CasterType::Full],
        'Paladino' => ['Paladino', CasterType::Half],
        'Ranger' => ['Ranger', CasterType::Half],
        'Warlock' => ['Warlock', CasterType::Pact],
        'Barbaro' => ['Barbaro', CasterType::None],
        'Guerriero' => ['Guerriero', CasterType::None],
    ]);

    it('diventa un terzo con le sottoclassi da incantatore', function () {
        expect(CasterType::for('Guerriero', 'Cavaliere Mistico'))->toBe(CasterType::Third)
            ->and(CasterType::for('Ladro', 'Furfante Arcano'))->toBe(CasterType::Third);
    });

    it('resta "nessuno" con le altre sottoclassi', function () {
        expect(CasterType::for('Guerriero', 'Campione'))->toBe(CasterType::None)
            ->and(CasterType::for('Ladro', 'Assassino'))->toBe(CasterType::None);
    });

    it('non promuove una classe che già lancia', function () {
        expect(CasterType::for('Mago', 'Evocazione'))->toBe(CasterType::Full);
    });

    it('tratta una classe sconosciuta come non incantatore', function () {
        expect(CasterType::for('Pasticciere'))->toBe(CasterType::None)
            ->and(CasterType::for(null))->toBe(CasterType::None);
    });
});

describe('slot degli incantatori completi', function () {
    it('al primo livello dà due slot di primo', function () {
        expect(SpellSlots::for(CasterType::Full, 1)->slots)->toBe([1 => 2]);
    });

    it('al quinto sblocca il terzo livello', function () {
        expect(SpellSlots::for(CasterType::Full, 5)->slots)->toBe([1 => 4, 2 => 3, 3 => 2]);
    });

    it('al ventesimo ha la tabella completa', function () {
        expect(SpellSlots::for(CasterType::Full, 20)->slots)
            ->toBe([1 => 4, 2 => 3, 3 => 3, 4 => 3, 5 => 3, 6 => 2, 7 => 2, 8 => 1, 9 => 1]);
    });

    it('copre tutti i venti livelli senza buchi', function (int $level) {
        expect(SpellSlots::for(CasterType::Full, $level)->isEmpty())->toBeFalse();
    })->with(range(1, 20));
});

describe('slot degli incantatori a metà', function () {
    it('non ne ha al primo livello', function () {
        expect(SpellSlots::for(CasterType::Half, 1)->isEmpty())->toBeTrue();
    });

    it('parte dal secondo livello', function () {
        expect(SpellSlots::for(CasterType::Half, 2)->slots)->toBe([1 => 2]);
    });

    it('arriva al quinto livello di incantesimo al ventesimo', function () {
        expect(SpellSlots::for(CasterType::Half, 20)->maxSpellLevel())->toBe(5);
    });
});

describe('slot degli incantatori di un terzo', function () {
    it('non ne ha prima del terzo livello', function () {
        expect(SpellSlots::for(CasterType::Third, 1)->isEmpty())->toBeTrue()
            ->and(SpellSlots::for(CasterType::Third, 2)->isEmpty())->toBeTrue();
    });

    it('parte dal terzo livello', function () {
        expect(SpellSlots::for(CasterType::Third, 3)->slots)->toBe([1 => 2]);
    });

    it('arriva al quarto livello di incantesimo al diciannovesimo', function () {
        expect(SpellSlots::for(CasterType::Third, 19)->maxSpellLevel())->toBe(4);
    });
});

describe('Pact Magic del Warlock', function () {
    it('dà uno slot di primo livello al primo', function () {
        $set = SpellSlots::for(CasterType::Pact, 1);

        expect($set->slots)->toBe([1 => 1])
            ->and($set->isPact)->toBeTrue();
    });

    it('all\'undicesimo dà tre slot di quinto', function () {
        $set = SpellSlots::for(CasterType::Pact, 11);

        expect($set->slots)->toBe([5 => 3])
            ->and($set->total())->toBe(3)
            ->and($set->maxSpellLevel())->toBe(5);
    });

    it('si distingue dagli slot normali', function () {
        expect(SpellSlots::for(CasterType::Pact, 5)->isPact)->toBeTrue()
            ->and(SpellSlots::for(CasterType::Full, 5)->isPact)->toBeFalse();
    });
});

describe('chi non lancia incantesimi', function () {
    it('non ha slot né livello massimo', function () {
        $set = SpellSlots::for(CasterType::None, 20);

        expect($set->isEmpty())->toBeTrue()
            ->and($set->maxSpellLevel())->toBe(0)
            ->and($set->total())->toBe(0);
    });
});

describe('caratteristica da incantatore', function () {
    it('è quella della classe', function () {
        expect(SpellSlots::abilityFor('Mago'))->toBe(Ability::Int)
            ->and(SpellSlots::abilityFor('Chierico'))->toBe(Ability::Wis)
            ->and(SpellSlots::abilityFor('Stregone'))->toBe(Ability::Cha);
    });

    it('è nulla per chi non lancia', function () {
        expect(SpellSlots::abilityFor('Barbaro'))->toBeNull();
    });
});
