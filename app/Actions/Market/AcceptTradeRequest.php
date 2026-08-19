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
 * «Sì, ce l'ho»: la richiesta diventa una proposta di scambio vera.
 *
 * Chi accetta sceglie **dal proprio zaino** cosa dare, perché la richiesta
 * conteneva solo delle parole: «un amuleto» può essere l'Amuleto di Salute o
 * l'amuleto scheggiato che tiene da parte, e la differenza la sa lui.
 *
 * Da qui in poi è uno scambio come tutti gli altri: passa dal Supervisor, e
 * **chi aveva chiesto deve confermarlo**. Sono due conferme e non una perché
 * fra la domanda e la risposta possono passare giorni, e quello che aveva
 * offerto potrebbe non averlo più.
 *
 * Se chi accetta è sotto richiamo, la proposta resta in attesa di un via libera
 * e lo scambio non esiste ancora: la richiesta è comunque chiusa — la risposta
 * l'ha data — e resta senza scambio collegato finché un DM non decide.
 */
final class AcceptTradeRequest
{
    /** @param  list<array{name: string, qty?: int}>  $give  cosa dà chi accetta */
    public function handle(TradeRequest $request, User $actor, array $give = [], int $giveGp = 0): Trade|SupervisedAction
    {
        if (! $request->isOpen()) {
            throw new MarketException('Questa richiesta è già stata chiusa.');
        }

        if ($give === [] && $giveGp === 0) {
            throw new MarketException('Scegli cosa dare: senza, non c\'è niente da proporre.');
        }

        $from = $request->from()->first();
        $to = $request->to()->first();

        if ($from === null || $to === null) {
            throw new MarketException('Uno dei due personaggi non c\'è più.');
        }

        return DB::transaction(function () use ($request, $actor, $from, $to, $give, $giveGp) {
            /*
             * Le parti si girano, ed è giusto così: la proposta la fa chi ha
             * l'oggetto. Quello che l'altro aveva offerto diventa quello che si
             * chiede in cambio, senza che nessuno lo possa ritoccare.
             */
            $esito = app(Supervisor::class)->proposeTrade(
                actor: $actor,
                from: $to,
                to: $from,
                give: $give,
                want: $request->offeredNames()->map(fn (string $nome) => ['name' => $nome, 'qty' => 1])->all(),
                giveGp: $giveGp,
                wantGp: $request->offered_gp,
                // Il messaggio dice da dove nasce: senza, arriverebbe una
                // proposta a sorpresa da qualcuno a cui si era solo chiesto.
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
