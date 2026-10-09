<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Quanto costa usare una capacità nel proprio turno. `Passivo` è sempre attivo
 * e non costa niente: va in fondo, ma resta scritto.
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
