<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mette un oggetto in vendita.
 *
 * L'oggetto **esce subito** dall'inventario del venditore: da qui in poi è in
 * deposito presso l'annuncio (§4.7 del brief). È la regola che impedisce di
 * vendere due volte la stessa cosa, o di venderla e nel frattempo scambiarla.
 */
final class CreateListing
{
    public function handle(
        Character $seller,
        string $itemName,
        int $qty,
        int $price,
        ?User $actor = null,
    ): MarketListing {
        if ($qty < 1) {
            throw MarketException::invalidQuantity();
        }

        return DB::transaction(function () use ($seller, $itemName, $qty, $price, $actor) {
            $character = Character::whereKey($seller->getKey())->lockForUpdate()->firstOrFail();

            if (! $character->ownsItem($itemName, $qty)) {
                throw MarketException::itemNotOwned($itemName);
            }

            // Si legge la riga prima di toglierla: categoria, valore e
            // dettagli servono all'annuncio, che deve poter descrivere
            // l'oggetto anche quando la riga di inventario non c'è più.
            $source = $character->items()->where('name', $itemName)->first();

            $character->removeFromInventory($itemName, $qty);

            $listing = MarketListing::create([
                'seller_character_id' => $character->getKey(),
                'name' => $itemName,
                'category' => $source?->category,
                'qty' => $qty,
                'price' => $price,
                'unit_value' => $source?->value ?? 0,
                'details' => $source?->details,
            ]);

            $character->recordInLedger(
                LedgerAction::SellList,
                "Messo in vendita {$qty}× {$itemName} per {$price} mo",
                actor: $actor,
            );

            return $listing;
        });
    }
}
