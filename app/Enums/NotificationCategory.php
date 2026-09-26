<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * I gruppi in cui il giocatore accende e spegne le email.
 *
 * Un interruttore per notifica sarebbe una pagina che nessuno legge, e uno
 * solo costringerebbe a zittire le sessioni per zittire il mercato. Le
 * categorie stanno nel mezzo, e sono il punto in cui il rumore si toglie
 * senza perdere quello che serve.
 *
 * Le preferenze salvano **solo** chi ha spento qualcosa: una categoria
 * aggiunta qui domani parte accesa per tutti, senza toccare i dati.
 */
enum NotificationCategory: string
{
    case Table = 'tavolo';
    case Requests = 'richieste';
    case Market = 'mercato';
    case Moderation = 'richiami';
    case Reports = 'segnalazioni';

    public function label(): string
    {
        return match ($this) {
            self::Table => 'Gioco al tavolo',
            self::Requests => 'Le mie richieste',
            self::Market => 'Mercato',
            self::Moderation => 'Richiami',
            self::Reports => 'Segnalazioni',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Table => 'Eventi, sessioni programmate, posto confermato a un incarico.',
            self::Requests => 'Proposte approvate o rifiutate, e quello che tocca a te approvare.',
            self::Market => 'Scambi proposti, annunci venduti, transazioni annullate.',
            self::Moderation => 'Richiami ricevuti e revocati.',
            self::Reports => 'Che fine hanno fatto i problemi che hai segnalato.',
        };
    }
}
