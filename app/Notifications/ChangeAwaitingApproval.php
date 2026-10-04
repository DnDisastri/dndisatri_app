<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\PendingChange;
use Illuminate\Support\Str;

/**
 * Avvisa DM e admin che in bacheca c'è una modifica da esaminare.
 */
final class ChangeAwaitingApproval extends InAppNotification
{
    public function __construct(private readonly PendingChange $change) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Approvals;
    }

    public function toArray(object $notifiable): array
    {
        $chi = $this->change->character?->name ?? 'Un personaggio';
        $cosa = filled($this->change->summary)
            ? Str::limit($this->change->summary, 300)
            : $this->change->type->label();

        return [
            'title' => 'Una modifica da approvare',
            'body' => "Richiesta di {$chi}.\n{$cosa}\nAprila nel pannello per decidere.",
            'url' => route('filament.admin.resources.pending-changes.view', $this->change),
        ];
    }
}
