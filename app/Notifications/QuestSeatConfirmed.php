<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quest;

/**
 * Il dungeon master ha confermato la serata: il posto è tuo.
 *
 * È l'avviso che chiude il giro delle prenotazioni. Senza, il giocatore
 * resterebbe a chiedersi se quella sera si gioca — che è esattamente il dubbio
 * che le prenotazioni esistono per togliere.
 */
final class QuestSeatConfirmed extends InAppNotification
{
    public function __construct(private readonly Quest $quest) {}

    public function toArray(object $notifiable): array
    {
        $campagna = $this->quest->campaign?->title;

        return [
            'title' => 'La serata si fa: hai un posto',
            'body' => "«{$this->quest->title}»"
                .($campagna ? " — {$campagna}" : '')
                .'. Il tuo posto è confermato.',
            'url' => $campagna && $this->quest->campaign
                ? route('campaigns.show', $this->quest->campaign)
                : null,
        ];
    }
}
