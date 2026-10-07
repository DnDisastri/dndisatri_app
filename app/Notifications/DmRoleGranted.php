<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * «Sei un dungeon master».
 *
 * Serve perché questa nomina arriva senza che la persona l'abbia chiesta: chi
 * passa da una richiesta riceve già `DmRequestDecided`, chi viene nominato di
 * sua iniziativa si troverebbe poteri nuovi senza sapere perché.
 */
final class DmRoleGranted extends InAppNotification
{
    public function category(): NotificationCategory
    {
        return NotificationCategory::Requests;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Sei un dungeon master',
            'body' => 'Un amministratore ti ha nominato. Ora puoi aprire campagne, programmare sessioni e condurre quest.',
            'url' => route('dm.home'),
        ];
    }
}
