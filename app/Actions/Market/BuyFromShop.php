<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Articolo e personaggio vanno bloccati entrambi: senza il primo due acquisti
 * dell'ultimo pezzo mandano le scorte sotto zero, senza il secondo due schede
 * aperte spendono da una borsa letta prima dell'altro acquisto.
 */
final class BuyFromShop
{
    public function handle(Character $character, MarketItem $item, int $qty = 1, ?User $actor = null): Character
    {
        if ($qty < 1) {
            throw MarketException::invalidQuantity();
        }

        return DB::transaction(function () use ($character, $item, $qty, $actor) {
            $item = MarketItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $buyer = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();

            if (! $item->isAvailable($qty)) {
                throw MarketException::outOfStock($item->name);
            }

            $paid = app(Purse::class)->pay($buyer, $item->totalPrice($qty));

            if (! $item->is_unlimited) {
                $item->decrement('stock', $qty);
            }

            $buyer->addToInventory(...Character::itemCopy($item), qty: $qty);

            $buyer->recordInLedger(
                LedgerAction::Buy,
                $qty > 1
                    ? "Acquisto di {$qty}× {$item->name} dall'Emporio"
                    : "Acquisto di {$item->name} dall'Emporio",
                $paid,
                $actor,
                // Per l'annullamento: dalla frase del messaggio non si torna indietro.
                [
                    'market_item_id' => $item->getKey(),
                    'name' => $item->name,
                    'qty' => $qty,
                ],
            );

            return $buyer;
        });
    }
}
