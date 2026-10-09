<?php

declare(strict_types=1);

namespace App\Enums;

enum EncounterStatus: string
{
    case Prepared = 'prepared';
    case Running = 'running';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Preparato',
            self::Running => 'In corso',
            self::Ended => 'Concluso',
        };
    }

    /** Il tono di `<x-badge>`. */
    public function tone(): string
    {
        return match ($this) {
            self::Running => 'own',
            self::Prepared => 'accent',
            self::Ended => 'neutral',
        };
    }
}
