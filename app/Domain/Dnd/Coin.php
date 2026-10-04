<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/** Il valore di ogni caso coincide con la colonna della pila su `characters`. */
enum Coin: string
{
    case Platinum = 'pp';
    case Gold = 'gp';
    case Silver = 'sp';
    case Copper = 'cp';

    /** Quanto vale una moneta, in rame. */
    public function value(): int
    {
        return match ($this) {
            self::Platinum => 1000,
            self::Gold => 100,
            self::Silver => 10,
            self::Copper => 1,
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::Platinum => 'mp',
            self::Gold => 'mo',
            self::Silver => 'ma',
            self::Copper => 'mr',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Platinum => 'Platino',
            self::Gold => 'Oro',
            self::Silver => 'Argento',
            self::Copper => 'Rame',
        };
    }

    /** @return list<self> dalla più grande alla più piccola */
    public static function descending(): array
    {
        return [self::Platinum, self::Gold, self::Silver, self::Copper];
    }
}
