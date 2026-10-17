<?php

declare(strict_types=1);

namespace App\Enums;

enum ArmorType: string
{
    case Light = 'light';
    case Medium = 'medium';
    case Heavy = 'heavy';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Leggera',
            self::Medium => 'Media',
            self::Heavy => 'Pesante',
        };
    }

    /** Il tetto di Destrezza del manuale, proposto quando si sceglie il tipo. */
    public function defaultDexCap(): ?int
    {
        return match ($this) {
            self::Light => null,
            self::Medium => 2,
            self::Heavy => 0,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
