<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\GameSession;

/** Un nuovo tavolo è in programma. */
final class GameSessionScheduled extends InAppNotification
{
    public function __construct(private readonly GameSession $session) {}

    public function toArray(object $notifiable): array
    {
        $campagna = $this->session->campaign?->title;

        return [
            'title' => 'Nuovo tavolo in programma',
            'body' => ($campagna ? "«{$campagna}» — " : '')
                ."{$this->session->displayTitle()}, il {$this->session->played_at->translatedFormat('j F \\a\\l\\l\\e H:i')}.",
            'url' => route('sessions.show', $this->session),
        ];
    }
}
