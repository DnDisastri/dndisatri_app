<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lo stato di un posto a una quest.
 *
 * Prenotarsi non è più iscriversi: il giocatore dichiara di volerci essere, e
 * il posto diventa suo quando il dungeon master conferma che la serata si fa.
 * Serve a sapere **quanti vogliono giocare** prima di decidere se il tavolo
 * parte — con sei posti e due interessati la serata non ha senso, e prima
 * questa informazione non esisteva da nessuna parte.
 *
 * Il dungeon master **non rifiuta nessuno**: conferma la serata per tutti
 * insieme, oppure dice che non si arriva al minimo. L'unica scelta sul singolo
 * giocatore è pescare dalla lista d'attesa quando un posto si libera.
 */
enum QuestSeatStatus: string
{
    /** Ha chiesto di esserci; il posto è tenuto, ma la serata non è confermata. */
    case Booked = 'booked';

    /** La serata si fa e il posto è suo. */
    case Confirmed = 'confirmed';

    /** I posti erano esauriti: entra se qualcuno si ritira. */
    case Waiting = 'waiting';

    /**
     * Si è tirato indietro. La riga **resta**: lo storico di chi voleva
     * giocare è metà del motivo per cui esistono le prenotazioni.
     */
    case Withdrawn = 'withdrawn';

    /**
     * Come si chiama questo stato **parlando di qualcun altro**: è quello che
     * si legge nell'elenco dei prenotati, accanto al nome di chi c'è.
     */
    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Prenotato',
            self::Confirmed => 'Confermato',
            self::Waiting => 'In lista d\'attesa',
            self::Withdrawn => 'Ritirato',
        };
    }

    /**
     * Come si chiama **quando sei tu**.
     *
     * Sono due voci diverse e servono tutte e due: «Prenotato» accanto al nome
     * di Marco è giusto, ma la stessa parola sulla tua card non dice che sei
     * stato tu — dice solo che qualcosa è prenotato. Tenere un metodo solo
     * significava scegliere quale delle due frasi sacrificare.
     */
    public function mine(): string
    {
        return match ($this) {
            self::Booked => 'Hai prenotato',
            self::Confirmed => 'Posto confermato',
            self::Waiting => 'In lista d\'attesa',
            self::Withdrawn => 'Ti sei ritirato',
        };
    }

    /** Occupa un posto: prenotati e confermati insieme riempiono il tavolo. */
    public function takesSeat(): bool
    {
        return $this === self::Booked || $this === self::Confirmed;
    }

    /** In gioco: né ritirato, né in attesa di un posto che si liberi. */
    public function isActive(): bool
    {
        return $this !== self::Withdrawn;
    }
}
