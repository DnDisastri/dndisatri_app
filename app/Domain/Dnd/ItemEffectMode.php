<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

enum ItemEffectMode: string
{
    /** Porta il punteggio al valore indicato, ma solo se è un miglioramento. */
    case Set = 'set';

    /** Somma algebrica al punteggio: accetta valori negativi. */
    case Bonus = 'bonus';
}
