<?php

declare(strict_types=1);

namespace App\Enums;

/** Come si contano gli usi di un privilegio. */
enum FeatureUsesFormula: string
{
    case Fixed = 'fixed';
    case Proficiency = 'proficiency';
    case HalfProficiency = 'half_proficiency';
    case Ability = 'ability';
    case AbilityPlusOne = 'ability_plus_one';
    case ClassLevel = 'class_level';
    case ClassLevelTimesFive = 'class_level_x5';
    case Table = 'table';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Un numero fisso',
            self::Proficiency => 'Bonus di competenza',
            self::HalfProficiency => 'Metà del bonus di competenza, per eccesso',
            self::Ability => 'Modificatore di una caratteristica (minimo 1)',
            self::AbilityPlusOne => 'Uno più il modificatore di una caratteristica',
            self::ClassLevel => 'Livello nella classe',
            self::ClassLevelTimesFive => 'Cinque volte il livello nella classe',
            self::Table => 'Tabella per livello',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $f) => [$f->value => $f->label()])->all();
    }
}
