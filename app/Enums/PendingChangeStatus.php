<?php

declare(strict_types=1);

namespace App\Enums;

enum PendingChangeStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In attesa',
            self::Approved => 'Approvata',
            self::Rejected => 'Rifiutata',
        };
    }

    public function isDecided(): bool
    {
        return $this !== self::Pending;
    }
}
