<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lo stato di un posto a una sessione.
 *
 * Il giocatore si prenota, e il posto diventa suo quando il DM conferma la
 * sessione. Il DM non rifiuta nessuno: conferma tutti insieme, o chiama dalla
 * lista d'attesa quando un posto si libera.
 */
enum SeatStatus: string
{
    case Booked = 'booked';

    case Confirmed = 'confirmed';

    /** I posti erano esauriti: entra se qualcuno si ritira. */
    case Waiting = 'waiting';

    /** La riga resta: lo storico di chi voleva giocare serve al DM. */
    case Withdrawn = 'withdrawn';

    /** Accanto al nome di qualcun altro. */
    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Prenotato',
            self::Confirmed => 'Confermato',
            self::Waiting => 'In lista d\'attesa',
            self::Withdrawn => 'Ritirato',
        };
    }

    /** Quando sei tu: «Prenotato» da solo non dice che sei stato tu. */
    public function mine(): string
    {
        return match ($this) {
            self::Booked => 'Hai prenotato',
            self::Confirmed => 'Posto confermato',
            self::Waiting => 'In lista d\'attesa',
            self::Withdrawn => 'Ti sei ritirato',
        };
    }

    public function takesSeat(): bool
    {
        return $this === self::Booked || $this === self::Confirmed;
    }

    public function isActive(): bool
    {
        return $this !== self::Withdrawn;
    }
}
