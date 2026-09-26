<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Models\Trade;
use App\Notifications\TradeResolved;
use InvalidArgumentException;

/**
 * Chiude una proposta di scambio senza eseguirla: rifiutata dal destinatario
 * o ritirata da chi l'ha fatta.
 *
 * Non muove niente, perché durante la proposta non era uscito niente dagli
 * inventari.
 */
final class ResolveTrade
{
    public function handle(Trade $trade, TradeStatus $status): Trade
    {
        if (! in_array($status, [TradeStatus::Rejected, TradeStatus::Cancelled], true)) {
            throw new InvalidArgumentException(
                'Qui si chiude una proposta senza eseguirla: per accettarla usa AcceptTrade.'
            );
        }

        if (! $trade->isOpen()) {
            throw new MarketException('Questa proposta di scambio è già stata chiusa.');
        }

        $trade->forceFill([
            'status' => $status,
            'resolved_at' => now(),
        ])->save();

        $from = $trade->from()->first();
        $to = $trade->to()->first();

        // Avvisa sempre chi **non** ha deciso: un rifiuto lo ha deciso il
        // destinatario, un ritiro chi aveva proposto.
        [$recipient, $otherName] = $status === TradeStatus::Rejected
            ? [$from, $to?->name]
            : [$to, $from?->name];

        $recipient?->user()->first()?->notify(new TradeResolved($trade, $otherName ?? 'Qualcuno'));

        return $trade;
    }
}
