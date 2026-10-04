<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\PendingChange;

/** Non dice mai chi ha deciso: gli admin non compaiono davanti ai giocatori. */
final class RequestDecided extends InAppNotification
{
    public function __construct(private readonly PendingChange $change) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Requests;
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->change->status->value === 'approved';
        $what = $this->change->type->label();

        return [
            'title' => $approved
                ? "{$what}: approvata"
                : "{$what}: rifiutata",
            'body' => trim(($this->change->summary ?: '').($this->change->review_note
                ? "\n\nNota: «{$this->change->review_note}»"
                : '')) ?: 'La tua richiesta è stata esaminata.',
            'url' => route('proposals.index'),
        ];
    }
}
