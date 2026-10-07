<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SessionUnavailableException extends RuntimeException
{
    public static function closed(): self
    {
        return new self('Questa sessione è già cominciata: le prenotazioni sono chiuse.');
    }

    public static function full(): self
    {
        return new self('Non ci sono più posti liberi in questa sessione.');
    }

    public static function notAParticipant(): self
    {
        return new self('Non risulti prenotato a questa sessione.');
    }

    public static function notWaiting(): self
    {
        return new self('Questo giocatore non è in lista d\'attesa.');
    }

    public static function wrongCharacter(): self
    {
        return new self('Scegli uno dei tuoi personaggi in vita.');
    }
}
