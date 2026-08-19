<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Un annullamento che non si può fare.
 *
 * Esiste come eccezione a sé perché il suo messaggio **è** la funzionalità: chi
 * annulla deve sapere esattamente cosa glielo impedisce, per poter rimediare a
 * mano con l'oro e il bottino (decisione D12).
 */
final class ReversalException extends RuntimeException
{
    public static function itemGone(string $who, string $item): self
    {
        return new self(
            "{$who} non ha più «{$item}»: l'annullamento lo toglierebbe dal nulla. "
            .'Rimedia a mano, oppure recuperalo prima.'
        );
    }

    public static function goldGone(string $who, int $needed, int $available): self
    {
        return new self(
            "{$who} ha {$available} mo e ne servirebbero {$needed}: l'annullamento "
            .'manderebbe il saldo sotto zero. Rimedia a mano.'
        );
    }

    public static function alreadyReversed(): self
    {
        return new self('Questa transazione è già stata annullata.');
    }

    public static function notReversible(string $why): self
    {
        return new self($why);
    }
}
