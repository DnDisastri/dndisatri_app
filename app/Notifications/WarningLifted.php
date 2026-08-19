<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Warning;

/** «Il richiamo è stato tolto»: si torna a fare tutto senza chiedere. */
final class WarningLifted extends InAppNotification
{
    public function __construct(private readonly Warning $warning) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Il richiamo è stato tolto',
            'body' => trim(($this->warning->lift_note ?? '')
                .' Scambi, annunci e acquisti tornano liberi.'),
            'url' => null,
        ];
    }
}
