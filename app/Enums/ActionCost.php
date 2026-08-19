<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Quanto costa usare una capacità, nel proprio turno.
 *
 * È la distinzione che serve davvero al tavolo: quando tocca a te hai
 * un'azione, un'azione bonus e il movimento, e la domanda non è «cosa so
 * fare» ma «cosa ci sta in questo turno».
 *
 * `Passivo` non vuol dire inutile: vuol dire che ce l'hai sempre e non devi
 * spendere niente. Vanno in fondo perché non entrano nella scelta del turno,
 * ma restano scritte perché sono metà di quello che rende un personaggio
 * diverso da un altro.
 */
enum ActionCost: string
{
    case Action = 'azione';
    case Bonus = 'bonus';
    case Reaction = 'reazione';
    case Passive = 'passivo';

    public function label(): string
    {
        return match ($this) {
            self::Action => 'Azione',
            self::Bonus => 'Azione bonus',
            self::Reaction => 'Reazione',
            self::Passive => 'Sempre attive',
        };
    }


    /** L'ordine in cui si guardano quando è il tuo turno. */
    public static function ordered(): array
    {
        return [self::Action, self::Bonus, self::Reaction, self::Passive];
    }
}
