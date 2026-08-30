<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PendingChange;

/**
 * La richiesta del giocatore è stata decisa.
 *
 * **Non dice chi ha deciso**, ed è la regola più importante di questa classe:
 * gli admin non compaiono mai davanti ai giocatori, e nominare solo i DM
 * darebbe un elenco a metà. Chi ha deciso si legge dal pannello.
 */
final class RequestDecided extends InAppNotification
{
    public function __construct(private readonly PendingChange $change) {}

    public function toArray(object $notifiable): array
    {
        $approved = $this->change->status->value === 'approved';
        $what = $this->change->type->label();

        return [
            'title' => $approved
                ? "{$what}: approvata"
                : "{$what}: rifiutata",
            'body' => trim(($this->change->summary ?: '').' '.($this->change->review_note
                ? "Nota: {$this->change->review_note}"
                : '')) ?: 'La tua richiesta è stata esaminata.',
            'url' => route('proposals.index'),
        ];
    }
}
