<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
use App\Enums\TradeDirection;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\Trade;
use App\Notifications\TradeProposed;
use Illuminate\Support\Facades\DB;

/**
 * Qui non si muove niente: inventari e monete si toccano all'accettazione, dove
 * `AcceptTrade` rifà tutte le verifiche. Una proposta può quindi fallire più
 * tardi; adesso si controlla solo quello che non ha senso nemmeno chiedere.
 */
final class CreateTrade
{
    /**
     * @param  list<array{name: string, qty?: int}>  $give  gli oggetti offerti
     * @param  list<array{name: string, qty?: int}>  $want  quelli chiesti in cambio
     */
    public function handle(
        Character $from,
        Character $to,
        array $give = [],
        array $want = [],
        int $giveCp = 0,
        int $wantCp = 0,
        ?string $message = null,
    ): Trade {
        if ($from->is($to)) {
            throw new MarketException('Non puoi proporre uno scambio a te stesso.');
        }

        if (! $to->isAlive() || ! $from->isAlive()) {
            throw new MarketException('Non si scambia con un personaggio caduto.');
        }

        if ($giveCp < 0 || $wantCp < 0 || max($giveCp, $wantCp) > Coins::MAX) {
            throw MarketException::invalidQuantity();
        }

        if ($give === [] && $want === [] && $giveCp === 0 && $wantCp === 0) {
            throw new MarketException('Uno scambio vuoto non si propone.');
        }

        foreach ($give as $item) {
            $qty = (int) ($item['qty'] ?? 1);

            if ($qty < 1) {
                throw MarketException::invalidQuantity();
            }

            if (! $from->ownsItem($item['name'], $qty)) {
                throw MarketException::itemNotOwned($item['name']);
            }
        }

        if ($from->purseValue() < $giveCp) {
            throw MarketException::notEnoughCoins($giveCp, $from->purseValue());
        }

        return DB::transaction(function () use ($from, $to, $give, $want, $giveCp, $wantCp, $message) {
            $trade = Trade::create([
                'from_character_id' => $from->getKey(),
                'to_character_id' => $to->getKey(),
                'give_cp' => $giveCp,
                'want_cp' => $wantCp,
                'message' => $message,
            ]);

            $this->attach($trade, $give, TradeDirection::Give, $from);
            // Di quello che si chiede si copia solo il nome: i dati veri arrivano all'accettazione.
            $this->attach($trade, $want, TradeDirection::Want, $to);

            $to->user()->first()?->notify(new TradeProposed($trade, $from->name));

            return $trade;
        });
    }

    /** @param list<array{name: string, qty?: int}> $items */
    private function attach(Trade $trade, array $items, TradeDirection $direction, Character $owner): void
    {
        foreach ($items as $item) {
            $source = $owner->items()->where('name', $item['name'])->first();

            $trade->items()->create([
                'direction' => $direction,
                'name' => $item['name'],
                'category' => $source?->category,
                'qty' => (int) ($item['qty'] ?? 1),
                'value_cp' => $source?->value_cp ?? 0,
                'details' => $source?->details,
            ]);
        }
    }
}
