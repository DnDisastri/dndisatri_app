<?php

declare(strict_types=1);

namespace App\Enums;

/** Il tipo di una sessione speciale: le multitavolo di inizio e fine stagione, o altro. */
enum SpecialSessionType: string
{
    case Opening = 'opening';
    case Closing = 'closing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Apertura di stagione',
            self::Closing => 'Chiusura di stagione',
            self::Other => 'Sessione speciale',
        };
    }

    /** @return array<string, string> per le tendine del pannello */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
