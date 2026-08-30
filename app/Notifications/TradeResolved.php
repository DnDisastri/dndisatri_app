<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TradeStatus;
use App\Models\Trade;

/**
 * Lo scambio è stato chiuso: accettato, rifiutato o ritirato.
 *
 * Va a chi **non** ha deciso: chi accetta sa già di aver accettato.
 */
final class TradeResolved extends InAppNotification
{
    public function __construct(
        private readonly Trade $trade,
        private readonly string $otherName,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => match ($this->trade->status) {
                TradeStatus::Accepted => "{$this->otherName} ha accettato lo scambio",
                TradeStatus::Rejected => "{$this->otherName} ha rifiutato lo scambio",
                default => "{$this->otherName} ha ritirato la proposta di scambio",
            },
            'body' => $this->trade->status === TradeStatus::Accepted
                ? 'Oggetti e oro sono già passati di mano.'
                : 'Non è cambiato niente nel tuo inventario.',
            'url' => null,
        ];
    }
}
