<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Come una quest è uscita dallo stato attivo. Non è una colonna: si deriva da
 * `completed_at` e `closed_at`, che sono la fonte di verità.
 */
enum QuestOutcome: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'In corso',
            self::Completed => 'Completata',
            self::Closed => 'Chiusa',
        };
    }

    /** Completate e chiuse finiscono entrambe nel Libro Mastro. */
    public function isArchived(): bool
    {
        return $this !== self::Active;
    }
}
