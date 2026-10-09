<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\GameSession;
use App\Models\Quest;

/** Una quest che ti interessa è stata messa in una sessione: per giocarla ti prenoti lì. */
final class QuestScheduled extends InAppNotification
{
    public function __construct(
        private readonly Quest $quest,
        private readonly GameSession $session,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'La quest che ti interessa si gioca',
            'body' => "«{$this->quest->title}», "
                .$this->session->played_at->translatedFormat('l j F \\a\\l\\l\\e H:i')
                .'. Prenotati alla sessione per esserci.',
            'url' => route('sessions.show', $this->session),
        ];
    }
}
