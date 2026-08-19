<?php

declare(strict_types=1);

namespace App\Enums;

enum TradeDirection: string
{
    /** Ciò che offre chi ha proposto lo scambio. */
    case Give = 'give';

    /** Ciò che chiede in cambio. */
    case Want = 'want';
}
