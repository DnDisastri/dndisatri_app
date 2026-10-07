<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * «Non sei più un dungeon master», così l'Area Master sparita non sembra un guasto.
 * Il motivo non c'è: se ne parla di persona.
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
