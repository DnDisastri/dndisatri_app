<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;

/**
 * Avvisa gli admin che qualcuno si è iscritto e aspetta.
 *
 * Fino a qui l'unico segnale era il badge sul menù del pannello, che si vede
 * solo se un admin entra di sua iniziativa: un iscritto poteva restare fuori
 * per giorni senza che nessuno lo sapesse.
 */
final class RegistrationAwaitingApproval extends InAppNotification
{
    public function __construct(private readonly User $applicant) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Requests;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Un nuovo iscritto aspetta',
            'body' => "{$this->applicant->name} ({$this->applicant->email}) si è registrato e non può entrare finché non lo approvi.",
            'url' => '/admin/users',
        ];
    }
}
