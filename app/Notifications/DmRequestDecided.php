<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;

/**
 * La richiesta di diventare dungeon master è stata decisa.
 *
 * Anche qui senza il nome di chi ha deciso: sono sempre e solo gli admin, e
 * gli admin non compaiono davanti ai giocatori.
 */
final class DmRequestDecided extends InAppNotification
{
    public function __construct(private readonly DmRequest $request) {}

    public function toArray(object $notifiable): array
    {
        $approved = $this->request->status === PendingChangeStatus::Approved;

        return [
            'title' => $approved
                ? 'Sei un dungeon master'
                : 'La richiesta di diventare DM è stata rifiutata',
            'body' => ($approved
                ? 'Da adesso puoi aprire tavoli, dare quest e decidere sulle richieste degli altri. '
                : '')
                .($this->request->review_note ? "Nota: {$this->request->review_note}" : ''),
            'url' => null,
        ];
    }
}
