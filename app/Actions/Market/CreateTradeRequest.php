<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\TradeRequest;
use App\Notifications\TradeRequested;
use Illuminate\Support\Facades\DB;

/**
 * Chiede un oggetto che non si vede in vetrina: il nome non si controlla, lo sa
 * solo chi riceve. Non muove niente e non passa dal Supervisor: lo farà lo
 * scambio che ne nasce. Si controlla solo la propria offerta.
 */
final class CreateTradeRequest
{
    /** @param  list<string>  $offered  nomi di oggetti del proprio zaino */
    public function handle(
        Character $from,
        Character $to,
        string $wanted,
        array $offered = [],
        int $offeredCp = 0,
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

        if ($offeredCp < 0 || $offeredCp > Coins::MAX) {
            throw MarketException::invalidQuantity();
        }

        $offered = collect($offered)->filter()->unique()->values();

        if ($offered->isEmpty() && $offeredCp === 0) {
            throw new MarketException('Offri qualcosa in cambio: una richiesta senza offerta non si valuta.');
        }

        foreach ($offered as $name) {
            if (! $from->ownsItem($name)) {
                throw MarketException::itemNotOwned($name);
            }
        }

        if ($from->purseValue() < $offeredCp) {
            throw MarketException::notEnoughCoins($offeredCp, $from->purseValue());
        }

        return DB::transaction(function () use ($from, $to, $wanted, $offered, $offeredCp, $message) {
            $request = TradeRequest::create([
                'from_character_id' => $from->getKey(),
                'to_character_id' => $to->getKey(),
                'wanted' => $wanted,
                'offered' => $offered->all(),
                'offered_cp' => $offeredCp,
                'message' => $message,
            ]);

            $to->user()->first()?->notify(new TradeRequested($request, $from->name));

            return $request;
        });
    }
}
