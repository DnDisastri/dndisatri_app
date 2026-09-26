<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * I bonus ai tiri: attacchi, tiri salvezza, prove di abilità, CD incantesimi.
 * Tutti partono dai punteggi EFFICACI.
 */
final class Checks
{
    /** CD per resistere agli incantesimi del personaggio. */
    public static function spellSaveDc(AbilityScores $effective, Ability $castingAbility, int $proficiencyBonus): int
    {
        return 8 + $effective->modifier($castingAbility) + $proficiencyBonus;
    }

    public static function spellAttack(AbilityScores $effective, Ability $castingAbility, int $proficiencyBonus): int
    {
        return $effective->modifier($castingAbility) + $proficiencyBonus;
    }

    /**
     * Attacco con arma: modificatore della caratteristica dell'arma, più la
     * competenza, più l'eventuale bonus magico dell'arma.
     */
    public static function weaponAttack(
        AbilityScores $effective,
        Ability $attackAbility,
        int $proficiencyBonus,
        int $weaponBonus = 0,
    ): int {
        return $effective->modifier($attackAbility) + $proficiencyBonus + $weaponBonus;
    }

    public static function savingThrow(
        AbilityScores $effective,
        Ability $ability,
        bool $proficient,
        int $proficiencyBonus,
    ): int {
        return $effective->modifier($ability) + ($proficient ? $proficiencyBonus : 0);
    }

    /**
     * Prova di abilità. L'Esperto vale una seconda volta il bonus di
     * competenza, e implica la competenza.
     *
     * @param  string  $skill  chiave tecnica in inglese, es. `stealth`
     */
    public static function skill(
        AbilityScores $effective,
        string $skill,
        SkillProficiency $proficiency,
        int $proficiencyBonus,
    ): int {
        $ability = Ability::from(config("dnd.character.skills.{$skill}"));

        return $effective->modifier($ability) + match ($proficiency) {
            SkillProficiency::None => 0,
            SkillProficiency::Proficient => $proficiencyBonus,
            SkillProficiency::Expert => $proficiencyBonus * 2,
        };
    }
}
