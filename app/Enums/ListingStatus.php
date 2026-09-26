<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingStatus: string
{
    case Active = 'active';
    case Sold = 'sold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'In vendita',
            self::Sold => 'Venduto',
            self::Cancelled => 'Ritirato',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Active;
    }
}
