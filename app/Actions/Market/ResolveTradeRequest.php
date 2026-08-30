<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Models\TradeRequest;
use App\Notifications\TradeRequestResolved;
use InvalidArgumentException;

/**
 * Chiude una richiesta senza che ne nasca uno scambio: rifiutata da chi l'ha
 * ricevuta, o ritirata da chi l'aveva fatta.
 *
 * Non muove niente, perché una richiesta non aveva mosso niente. Per il sì c'è
 * `AcceptTradeRequest`, che è un'altra cosa: lì nasce una proposta.
 */
final class ResolveTradeRequest
{
    public function handle(TradeRequest $request, TradeStatus $status): TradeRequest
    {
        if (! in_array($status, [TradeStatus::Rejected, TradeStatus::Cancelled], true)) {
            throw new InvalidArgumentException(
                'Qui si chiude una richiesta senza risponderle: per il sì usa AcceptTradeRequest.'
            );
        }

        if (! $request->isOpen()) {
            throw new MarketException('Questa richiesta è già stata chiusa.');
        }

        $request->forceFill([
            'status' => $status,
            'resolved_at' => now(),
        ])->save();

        $from = $request->from()->first();
        $to = $request->to()->first();

        // Si avvisa chi **non** ha deciso. Ritirando la propria richiesta si
        // avvisa l'altro solo se stava ancora aspettando di rispondere.
        [$destinatario, $altro] = $status === TradeStatus::Rejected
            ? [$from, $to?->name]
            : [$to, $from?->name];

        $destinatario?->user()->first()?->notify(
            new TradeRequestResolved($request, $altro ?? 'Qualcuno'),
        );

        return $request;
    }
}
