<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Domain\Dnd\Coins;
use RuntimeException;

class MarketException extends RuntimeException
{
    /** Valori in rame. */
    public static function notEnoughCoins(int $needed, int $available): self
    {
        return new self('Servono '.Coins::formatValue($needed).', ma la borsa vale '.Coins::formatValue($available).'.');
    }

    public static function outOfStock(string $item): self
    {
        return new self("«{$item}» è esaurito.");
    }

    public static function itemNotOwned(string $item): self
    {
        return new self("«{$item}» non è nel tuo inventario.");
    }

    public static function invalidQuantity(): self
    {
        return new self('La quantità deve essere almeno 1.');
    }
}
