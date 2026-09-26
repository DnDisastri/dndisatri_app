<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class QuestUnavailableException extends RuntimeException
{
    public static function notActive(): self
    {
        return new self('Questa quest è già conclusa: non accetta più prenotazioni né modifiche.');
    }

    public static function full(): self
    {
        return new self('Non ci sono più posti liberi in questa quest.');
    }

    public static function notAParticipant(): self
    {
        return new self('Non risulti prenotato a questa quest.');
    }

    public static function notWaiting(): self
    {
        return new self('Questo giocatore non è in lista d\'attesa.');
    }
}
