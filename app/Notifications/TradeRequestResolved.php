<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TradeStatus;
use App\Models\TradeRequest;

/**
 * La richiesta è stata chiusa senza diventare uno scambio: rifiutata o
 * ritirata. Va a chi **non** ha deciso.
 *
 * Del sì non si avvisa qui: da quello nasce una proposta di scambio, e ad
 * annunciarla ci pensa `TradeProposed`. Due notifiche per lo stesso momento
 * sarebbero una di troppo.
 */
final class TradeRequestResolved extends InAppNotification
{
    public function __construct(
        private readonly TradeRequest $request,
        private readonly string $otherName,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->request->status === TradeStatus::Rejected
                ? "{$this->otherName} ha rifiutato la tua richiesta"
                : "{$this->otherName} ha ritirato la richiesta",
            'body' => "Riguardava «{$this->request->wanted}». Non è cambiato niente nel tuo inventario.",
            'url' => null,
        ];
    }
}
