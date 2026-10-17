<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case VeryRare = 'very_rare';
    case Legendary = 'legendary';
    case Artifact = 'artifact';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Comune',
            self::Uncommon => 'Non comune',
            self::Rare => 'Raro',
            self::VeryRare => 'Molto raro',
            self::Legendary => 'Leggendario',
            self::Artifact => 'Artefatto',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
