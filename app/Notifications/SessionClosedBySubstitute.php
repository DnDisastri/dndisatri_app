<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\GameSession;

/** Al DM della campagna: un collega ha chiuso al posto suo una parte della sessione. */
final class SessionClosedBySubstitute extends InAppNotification
{
    public function __construct(
        private readonly GameSession $session,
        private readonly string $substitute,
        private readonly string $what,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function toArray(object $notifiable): array
    {
        $campagna = $this->session->campaign?->title;

        return [
            'title' => "{$this->substitute} ha salvato {$this->what}",
            'body' => "Al posto tuo, per {$this->session->displayTitle()}".($campagna ? " di «{$campagna}»" : '').'.',
            'url' => route('sessions.show', $this->session),
        ];
    }
}
