<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Enums\ListingStatus;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\User;
use App\Notifications\ListingSold;
use Illuminate\Support\Facades\DB;

/** L'oggetto è già in deposito presso l'annuncio: qui si muovono monete e consegna. */
final class BuyListing
{
    public function handle(MarketListing $listing, Character $buyer, ?User $actor = null): MarketListing
    {
        return DB::transaction(function () use ($listing, $buyer, $actor) {
            $locked = MarketListing::whereKey($listing->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new MarketException('Questo annuncio non è più disponibile.');
            }

            if ($locked->seller_character_id === $buyer->getKey()) {
                throw new MarketException('Non puoi comprare un tuo stesso annuncio.');
            }

            $purchaser = Character::whereKey($buyer->getKey())->lockForUpdate()->firstOrFail();
            $seller = Character::whereKey($locked->seller_character_id)->lockForUpdate()->firstOrFail();

            $purse = app(Purse::class);
            $paid = $purse->pay($purchaser, $locked->price_cp);
            $received = $purse->receiveValue($seller, $locked->price_cp);

            $purchaser->addToInventory(
                name: $locked->name,
                qty: $locked->qty,
                category: $locked->category,
                valueCp: $locked->unit_value_cp,
                details: $locked->details,
            );

            $locked->forceFill([
                'status' => ListingStatus::Sold,
                'buyer_character_id' => $purchaser->getKey(),
                'resolved_at' => now(),
            ])->save();

            $description = "{$locked->qty}× {$locked->name}";

            $purchaser->recordInLedger(
                LedgerAction::ListingBought,
                "Comprato {$description} da {$seller->name}",
                $paid,
                $actor,
            );

            $seller->recordInLedger(
                LedgerAction::ListingSold,
                "Venduto {$description} a {$purchaser->name}",
                $received,
                $actor,
            );

            $seller->user()->first()?->notify(new ListingSold($locked, $purchaser->name));

            return $locked;
        });
    }
}
