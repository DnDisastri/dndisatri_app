<?php

declare(strict_types=1);

namespace App\Enums;

enum TradeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In attesa',
            self::Accepted => 'Accettato',
            self::Rejected => 'Rifiutato',
            self::Cancelled => 'Ritirato',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }
}
