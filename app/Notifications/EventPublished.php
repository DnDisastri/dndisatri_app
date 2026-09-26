<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Event;

/** Un nuovo evento è comparso in bacheca. */
final class EventPublished extends InAppNotification
{
    public function __construct(private readonly Event $event) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nuovo evento',
            'body' => "«{$this->event->title}», il {$this->event->starts_at->translatedFormat('j F')}.",
            'url' => route('events.show', $this->event),
        ];
    }
}
