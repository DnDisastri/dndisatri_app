<?php

declare(strict_types=1);

namespace App\Enums;

/** Le taglie delle creature, dalla più piccola. */
enum CreatureSize: string
{
    case Tiny = 'tiny';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Huge = 'huge';
    case Gargantuan = 'gargantuan';

    public function label(): string
    {
        return match ($this) {
            self::Tiny => 'Minuscola',
            self::Small => 'Piccola',
            self::Medium => 'Media',
            self::Large => 'Grande',
            self::Huge => 'Enorme',
            self::Gargantuan => 'Mastodontica',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
