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
        return new self('Non risulti fra chi ha chiesto un posto in questa sessione.');
    }

    public static function cannotOffer(): self
    {
        return new self('A questa persona non si può offrire un posto adesso.');
    }

    public static function noOffer(): self
    {
        return new self('Non c\'è un posto da confermare: forse la conferma è scaduta.');
    }

    public static function noReserveQuestion(): self
    {
        return new self('Non c\'è nessuna domanda a cui rispondere.');
    }

    public static function sameDay(): self
    {
        return new self('Quel giorno hai già chiesto un posto a un\'altra sessione: non ci si sdoppia fra due tavoli.');
    }

    public static function wrongCharacter(): self
    {
        return new self('Scegli uno dei tuoi personaggi in vita.');
    }
}
