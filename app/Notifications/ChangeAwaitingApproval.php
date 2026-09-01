<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PendingChange;

/**
 * Avvisa DM e admin che in bacheca c'è una modifica da esaminare.
 */
final class ChangeAwaitingApproval extends InAppNotification
{
    public function __construct(private readonly PendingChange $change) {}

    public function toArray(object $notifiable): array
    {
        $chi = $this->change->character?->name ?? 'Un personaggio';

        return [
            'title' => 'Una modifica da approvare',
            'body' => "{$chi}: {$this->change->type->label()}. Aprila nel pannello per decidere.",
            'url' => '/admin/pending-changes',
        ];
    }
}
