<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * «Non sei più un dungeon master».
 *
 * Perdere dei poteri senza che nessuno lo dica è peggio che perderli: il
 * giocatore troverebbe la regia sparita dal menù e penserebbe a un guasto.
 * Non dice il motivo, che è una conversazione fra persone e non una riga
 * dentro una notifica.
 */
final class DmRoleRevoked extends InAppNotification
{
    public function category(): NotificationCategory
    {
        return NotificationCategory::Requests;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Non conduci più',
            'body' => 'Un amministratore ti ha riportato fra i giocatori. Torni a poter avere un personaggio tuo.',
            'url' => route('home'),
        ];
    }
}
