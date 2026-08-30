<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\TradeRequest;
use App\Models\User;
use App\Notifications\TradeRequested;
use Illuminate\Support\Facades\DB;

/**
 * Chiede un oggetto che non si vede: «mi han detto che hai un Amuleto, lo dai?»
 *
 * Esiste perché lo zaino di un altro non è pubblico: in vetrina finisce solo
 * quello che il proprietario ci mette, e per tutto il resto non c'è niente da
 * spuntare. Quindi il nome si scrive a mano, e **non si controlla**: chi chiede
 * non può sapere se quell'oggetto c'è davvero, ed è il punto della richiesta.
 * Sarà chi la riceve a dire di sì o di no, che è l'unico che lo sa.
 *
 * **Non muove niente e non passa dal Supervisor.** Una domanda non è
 * un'operazione di mercato: se ne nasce uno scambio, sarà quello a passare
 * dalla vigilanza, e a proporlo sarà l'altro.
 *
 * Quello che invece si controlla è la propria metà: offrire una cosa che non si
 * ha non è una circostanza che può cambiare, è un errore di compilazione.
 */
final class CreateTradeRequest
{
    /** @param  list<string>  $offered  nomi di oggetti del proprio zaino */
    public function handle(
        Character $from,
        Character $to,
        string $wanted,
        array $offered = [],
        int $offeredGp = 0,
        ?string $message = null,
    ): TradeRequest {
        $wanted = trim($wanted);

        if ($from->is($to)) {
            throw new MarketException('Non puoi chiedere niente a te stesso.');
        }

        if (! $to->isAlive() || ! $from->isAlive()) {
            throw new MarketException('Non si scambia con un personaggio caduto.');
        }

        if ($wanted === '') {
            throw new MarketException('Scrivi che cosa vorresti: è la domanda.');
        }

        if ($offeredGp < 0) {
            throw MarketException::invalidQuantity();
        }

        $offered = collect($offered)->filter()->unique()->values();

        if ($offered->isEmpty() && $offeredGp === 0) {
            throw new MarketException('Offri qualcosa in cambio: una richiesta senza offerta non si valuta.');
        }

        foreach ($offered as $name) {
            if (! $from->ownsItem($name)) {
                throw MarketException::itemNotOwned($name);
            }
        }

        if ($from->gp < $offeredGp) {
            throw MarketException::notEnoughGold($offeredGp, $from->gp);
        }

        return DB::transaction(function () use ($from, $to, $wanted, $offered, $offeredGp, $message) {
            $request = TradeRequest::create([
                'from_character_id' => $from->getKey(),
                'to_character_id' => $to->getKey(),
                'wanted' => $wanted,
                'offered' => $offered->all(),
                'offered_gp' => $offeredGp,
                'message' => $message,
            ]);

            // Una domanda che nessuno sa di aver ricevuto non è una domanda.
            $to->user()->first()?->notify(new TradeRequested($request, $from->name));

            return $request;
        });
    }
}
