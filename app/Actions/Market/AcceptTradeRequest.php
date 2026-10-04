<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Actions\Supervision\Supervisor;
use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * La richiesta diventa uno scambio, che passa dal Supervisor e va confermato da
 * chi aveva chiesto: nel frattempo la sua offerta può non esserci più. Sotto
 * richiamo la richiesta si chiude comunque, senza scambio finché un DM non decide.
 */
final class AcceptTradeRequest
{
    /** @param  list<array{name: string, qty?: int}>  $give  cosa dà chi accetta */
    public function handle(TradeRequest $request, User $actor, array $give = [], int $giveCp = 0): Trade|SupervisedAction
    {
        if (! $request->isOpen()) {
            throw new MarketException('Questa richiesta è già stata chiusa.');
        }

        if ($give === [] && $giveCp === 0) {
            throw new MarketException('Scegli cosa dare: senza, non c\'è niente da proporre.');
        }

        $from = $request->from()->first();
        $to = $request->to()->first();

        if ($from === null || $to === null) {
            throw new MarketException('Uno dei due personaggi non c\'è più.');
        }

        return DB::transaction(function () use ($request, $actor, $from, $to, $give, $giveCp) {
            // Le parti si girano: propone chi ha l'oggetto, e l'offerta di chi
            // aveva chiesto diventa, senza ritocchi, quello che si chiede in cambio.
            $esito = app(Supervisor::class)->proposeTrade(
                actor: $actor,
                from: $to,
                to: $from,
                give: $give,
                want: $request->offeredNames()->map(fn (string $nome) => ['name' => $nome, 'qty' => 1])->all(),
                giveCp: $giveCp,
                wantCp: $request->offered_cp,
                message: "In risposta alla tua richiesta: «{$request->wanted}».",
            );

            $request->forceFill([
                'status' => TradeStatus::Accepted,
                'resolved_at' => now(),
                'trade_id' => $esito instanceof Trade ? $esito->getKey() : null,
            ])->save();

            return $esito;
        });
    }
}
