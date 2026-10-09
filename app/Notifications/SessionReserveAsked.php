<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\GameSession;

/** I posti sono tutti confermati: chi ha chiesto e non è stato scelto può restare come riserva. */
final class SessionReserveAsked extends InAppNotification
{
    public function __construct(private readonly GameSession $session) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function alwaysEmail(): bool
    {
        return true;
    }

    public function buttonLabel(): string
    {
        return 'Rispondi';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Sessione piena: resti fra le riserve?',
            'body' => GuestBookingMail::sessionLine($this->session)."\n"
                .'I posti sono tutti confermati. Se resti fra le riserve, potresti essere chiamato '
                .'se qualcuno si ritira, ma non è garantito.',
            'url' => route('sessions.show', $this->session),
        ];
    }
}
