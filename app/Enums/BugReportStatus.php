<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dove sta una segnalazione.
 *
 * Chiuso si dice in due modi perché al segnalante servono due messaggi
 * diversi: «è a posto» e «funziona così».
 */
enum BugReportStatus: string
{
    case Open = 'aperta';
    case InProgress = 'in-lavorazione';
    case Fixed = 'risolta';
    case NotABug = 'non-e-un-errore';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aperta',
            self::InProgress => 'In lavorazione',
            self::Fixed => 'Risolta',
            self::NotABug => 'Non è un errore',
        };
    }

    /** Quelle su cui non tocca più decidere niente. */
    public function isClosed(): bool
    {
        return in_array($this, [self::Fixed, self::NotABug], true);
    }

    /** Il colore del badge nel pannello. */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::InProgress => 'warning',
            self::Fixed => 'success',
            self::NotABug => 'gray',
        };
    }
}
