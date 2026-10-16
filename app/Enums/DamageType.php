<?php

declare(strict_types=1);

namespace App\Enums;

/** I tipi di danno del manuale, per gli attacchi dei mostri. */
enum DamageType: string
{
    case Slashing = 'slashing';
    case Piercing = 'piercing';
    case Bludgeoning = 'bludgeoning';
    case Acid = 'acid';
    case Cold = 'cold';
    case Fire = 'fire';
    case Force = 'force';
    case Lightning = 'lightning';
    case Necrotic = 'necrotic';
    case Poison = 'poison';
    case Psychic = 'psychic';
    case Radiant = 'radiant';
    case Thunder = 'thunder';

    /** Come segue «danni»: «1d8+1 danni taglienti». */
    public function label(): string
    {
        return match ($this) {
            self::Slashing => 'taglienti',
            self::Piercing => 'perforanti',
            self::Bludgeoning => 'contundenti',
            self::Acid => 'da acido',
            self::Cold => 'da freddo',
            self::Fire => 'da fuoco',
            self::Force => 'da forza',
            self::Lightning => 'da fulmine',
            self::Necrotic => 'necrotici',
            self::Poison => 'da veleno',
            self::Psychic => 'psichici',
            self::Radiant => 'radiosi',
            self::Thunder => 'da tuono',
        };
    }

    /** @return array<string, string> per le tendine, con l'iniziale maiuscola */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo) => [$tipo->value => ucfirst($tipo->label())])
            ->all();
    }
}
