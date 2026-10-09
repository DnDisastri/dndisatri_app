<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lo stato di un posto a una sessione.
 *
 * Il giocatore chiede, il DM sceglie chi vuole e il posto diventa suo solo
 * quando conferma entro la scadenza. A posti pieni chi non è stato scelto
 * può restare come riserva. Niente passa da solo da uno stato all'altro,
 * tranne l'offerta che scade.
 */
enum SeatStatus: string
{
    /** Un ospite che non ha ancora cliccato il link dell'email: non lo vede nessuno. */
    case Unverified = 'unverified';

    case Requested = 'requested';

    /** Scelto dal DM: il posto è tenuto da parte fino a `offer_expires_at`. */
    case Offered = 'offered';

    case Confirmed = 'confirmed';

    /** Sessione piena, ma ha detto che resta disponibile. */
    case Reserve = 'reserve';

    /** Non ha confermato in tempo: il posto è tornato libero. */
    case Expired = 'expired';

    /** La riga resta: lo storico di chi voleva giocare serve al DM. */
    case Withdrawn = 'withdrawn';

    /** Per il DM, nella pill accanto al nome. */
    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Email da verificare',
            self::Requested => 'Richiesta',
            self::Offered => 'Da confermare',
            self::Confirmed => 'Confermato',
            self::Reserve => 'Riserva',
            self::Expired => 'Scaduta',
            self::Withdrawn => 'Richiesta ritirata',
        };
    }

    /** Il tono della pill: il posto confermato spicca, la riserva si distingue, il resto è di passaggio. */
    public function tone(): string
    {
        return match ($this) {
            self::Confirmed => 'accent',
            self::Reserve => 'own',
            default => 'outline',
        };
    }

    /** Quando sei tu. */
    public function mine(): string
    {
        return match ($this) {
            self::Unverified => 'Controlla la tua email',
            self::Requested => 'Richiesta inviata',
            self::Offered => 'Hai un posto: confermalo',
            self::Confirmed => 'Posto confermato',
            self::Reserve => 'Sei fra le riserve',
            self::Expired => 'Conferma scaduta',
            self::Withdrawn => 'Hai ritirato la richiesta',
        };
    }

    /** Offerto o confermato: conta per i posti. */
    public function takesSeat(): bool
    {
        return $this === self::Offered || $this === self::Confirmed;
    }

    /** Il DM può offrirgli un posto. */
    public function canBeOffered(): bool
    {
        return in_array($this, [self::Requested, self::Reserve, self::Expired], true);
    }

    /** Ancora in gioco: chi si è ritirato o non ha verificato l'email no. */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Withdrawn, self::Unverified], true);
    }

    /** @return list<string> */
    public static function seatValues(): array
    {
        return [self::Offered->value, self::Confirmed->value];
    }
}
