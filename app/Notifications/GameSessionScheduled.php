<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\GameSession;

/** Una nuova sessione è in programma. */
final class GameSessionScheduled extends InAppNotification
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
            'title' => 'Nuova sessione in programma',
            'body' => ($campagna ? "«{$campagna}»: " : '')
                ."{$this->session->displayTitle()}, il {$this->session->played_at->translatedFormat('j F \\a\\l\\l\\e H:i')}. "
                ."{$this->session->max_players} posti: prenotati dalla pagina della sessione.",
            'url' => route('sessions.show', $this->session),
        ];
    }
}
