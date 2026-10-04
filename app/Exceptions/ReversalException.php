<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Domain\Dnd\Coins;
use RuntimeException;

/** Il messaggio dice cosa blocca l'annullamento, perché l'admin rimedi a mano. */
final class ReversalException extends RuntimeException
{
    public static function itemGone(string $who, string $item): self
    {
        return new self(
            "{$who} non ha più «{$item}»: l'annullamento lo toglierebbe dal nulla. "
            .'Rimedia a mano, oppure recuperalo prima.'
        );
    }

    /** Valori in rame. */
    public static function coinsGone(string $who, int $needed, int $available): self
    {
        return new self(
            "La borsa di {$who} vale ".Coins::formatValue($available).' e ne servirebbero '
            .Coins::formatValue($needed).": l'annullamento la manderebbe sotto zero. Rimedia a mano."
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
