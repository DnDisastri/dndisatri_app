<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;

/** Avvisa gli admin che un iscritto aspetta l'approvazione. */
final class RegistrationAwaitingApproval extends InAppNotification
{
    public function __construct(private readonly User $applicant) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Approvals;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Un nuovo iscritto aspetta',
            'body' => "{$this->applicant->name} ({$this->applicant->email}) si è registrato e non può entrare finché non lo approvi.",
            // L'elenco e non la scheda: «Approva» sta fra le azioni della tabella.
            'url' => route('filament.admin.resources.users.index'),
        ];
    }
}
