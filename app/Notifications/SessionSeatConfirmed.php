<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\GameSession;

/** Il DM ha confermato la sessione: il posto è tuo. */
final class SessionSeatConfirmed extends InAppNotification
{
    public function __construct(private readonly GameSession $session) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function toArray(object $notifiable): array
    {
        $campagna = $this->session->campaign?->title;

        return [
            'title' => 'Sessione confermata: hai un posto',
            'body' => ($campagna ? "«{$campagna}», " : '')
                .$this->session->played_at->translatedFormat('l j F \\a\\l\\l\\e H:i').'.',
            'url' => route('sessions.show', $this->session),
        ];
    }
}
