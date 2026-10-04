<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** L'oggetto esce subito dall'inventario: così non si vende due volte, né si scambia nel frattempo. */
final class CreateListing
{
    public function handle(
        Character $seller,
        string $itemName,
        int $qty,
        int $priceCp,
        ?User $actor = null,
    ): MarketListing {
        if ($qty < 1) {
            throw MarketException::invalidQuantity();
        }

        if ($priceCp < 0 || $priceCp > Coins::MAX) {
            throw new MarketException('Il prezzo non è valido.');
        }

        return DB::transaction(function () use ($seller, $itemName, $qty, $priceCp, $actor) {
            $character = Character::whereKey($seller->getKey())->lockForUpdate()->firstOrFail();

            if (! $character->ownsItem($itemName, $qty)) {
                throw MarketException::itemNotOwned($itemName);
            }

            // Letta prima di toglierla: l'annuncio deve descrivere l'oggetto anche dopo.
            $source = $character->items()->where('name', $itemName)->first();

            $character->removeFromInventory($itemName, $qty);

            $listing = MarketListing::create([
                'seller_character_id' => $character->getKey(),
                'name' => $itemName,
                'base' => $source?->base,
                'magic_bonus' => (int) $source?->magic_bonus,
                'category' => $source?->category,
                'qty' => $qty,
                'price_cp' => $priceCp,
                'unit_value_cp' => $source?->value_cp ?? 0,
                'details' => $source?->details,
            ]);

            $character->recordInLedger(
                LedgerAction::SellList,
                "Messo in vendita {$qty}× {$itemName} per ".Coins::formatValue($priceCp),
                actor: $actor,
            );

            return $listing;
        });
    }
}
