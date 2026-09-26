<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\Checks;
use App\Domain\Dnd\SkillProficiency;

describe('incantesimi', function () {
    it('la CD è 8 + caratteristica + competenza', function () {

        $scores = AbilityScores::fromArray(['int' => 18]);

        expect(Checks::spellSaveDc($scores, Ability::Int, 3))->toBe(15);
    });

    it('l\'attacco è caratteristica + competenza', function () {
        $scores = AbilityScores::fromArray(['cha' => 16]);

        expect(Checks::spellAttack($scores, Ability::Cha, 2))->toBe(5);
    });
});

describe('attacco con arma', function () {
    it('somma caratteristica, competenza e bonus magico', function () {
        $scores = AbilityScores::fromArray(['str' => 18]);

        expect(Checks::weaponAttack($scores, Ability::Str, 3, weaponBonus: 1))->toBe(8);
    });

    it('senza bonus magico somma solo caratteristica e competenza', function () {
        $scores = AbilityScores::fromArray(['dex' => 16]);

        expect(Checks::weaponAttack($scores, Ability::Dex, 2))->toBe(5);
    });
});

describe('tiri salvezza', function () {
    it('aggiungono la competenza solo dove il personaggio ce l\'ha', function () {
        $scores = AbilityScores::fromArray(['con' => 16, 'cha' => 8]);

        expect(Checks::savingThrow($scores, Ability::Con, proficient: true, proficiencyBonus: 3))->toBe(6)
            ->and(Checks::savingThrow($scores, Ability::Cha, proficient: false, proficiencyBonus: 3))->toBe(-1);
    });
});

describe('prove di abilità', function () {
    it('usano la caratteristica associata all\'abilità', function () {

        $scores = AbilityScores::fromArray(['dex' => 18, 'int' => 8]);

        expect(Checks::skill($scores, 'stealth', SkillProficiency::None, 3))->toBe(4)
            ->and(Checks::skill($scores, 'arcana', SkillProficiency::None, 3))->toBe(-1);
    });

    it('sommano la competenza una volta', function () {
        $scores = AbilityScores::fromArray(['dex' => 18]);

        expect(Checks::skill($scores, 'stealth', SkillProficiency::Proficient, 3))->toBe(7);
    });

    it('con Esperto sommano la competenza due volte', function () {
        $scores = AbilityScores::fromArray(['dex' => 18]);

        expect(Checks::skill($scores, 'stealth', SkillProficiency::Expert, 3))->toBe(10);
    });

    it('coprono tutte e diciotto le abilità', function () {
        $skills = array_keys(config('dnd.character.skills'));

        expect($skills)->toHaveCount(18);

        foreach ($skills as $skill) {
            expect(Checks::skill(new AbilityScores, $skill, SkillProficiency::Proficient, 2))->toBe(2);
        }
    });
});
