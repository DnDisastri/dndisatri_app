<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * Le categorie delle email. Le preferenze salvano solo quelle disattivate:
 * una categoria nuova parte attiva per tutti senza toccare i dati.
 */
enum NotificationCategory: string
{
    case Table = 'tavolo';
    case Requests = 'richieste';
    case Approvals = 'approvazioni';
    case Market = 'mercato';
    case Moderation = 'richiami';
    case Reports = 'segnalazioni';

    public function label(): string
    {
        return match ($this) {
            self::Table => 'Gioco al tavolo',
            self::Requests => 'Le mie richieste',
            self::Approvals => 'Da approvare',
            self::Market => 'Mercato',
            self::Moderation => 'Richiami',
            self::Reports => 'Segnalazioni',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Table => 'Eventi, sessioni programmate, posto confermato a un incarico.',
            self::Requests => 'Proposte approvate o rifiutate.',
            self::Approvals => 'Richieste dei giocatori, azioni sotto richiamo, nuovi iscritti e segnalazioni che aspettano una decisione.',
            self::Market => 'Scambi proposti, annunci venduti, transazioni annullate.',
            self::Moderation => 'Richiami ricevuti e revocati.',
            self::Reports => 'Che fine hanno fatto i problemi che hai segnalato.',
        };
    }

    /** @return list<self> */
    public static function forUser(User $user): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $categoria) => $categoria !== self::Approvals || $user->isDm() || $user->isAdmin(),
        ));
    }
}
