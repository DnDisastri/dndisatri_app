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

/**
 * Acquisto da un altro giocatore.
 *
 * L'oggetto è già in deposito presso l'annuncio, quindi qui si muovono l'oro
 * e la consegna. Tutto in una transazione con annuncio, compratore e venditore
 * bloccati: se salta un pezzo, non deve restare né oro sparito né oggetto
 * consegnato senza pagamento.
 *
 * Il Registro riceve due righe, una per parte: il bilancio dev'essere leggibile
 * da entrambi i lati.
 */
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

            if ($purchaser->gp < $locked->price) {
                throw MarketException::notEnoughGold($locked->price, $purchaser->gp);
            }

            $purchaser->decrement('gp', $locked->price);
            $seller->increment('gp', $locked->price);

            $purchaser->refresh()->addToInventory(
                name: $locked->name,
                qty: $locked->qty,
                category: $locked->category,
                value: $locked->unit_value,
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
                -$locked->price,
                $actor,
            );

            $seller->refresh()->recordInLedger(
                LedgerAction::ListingSold,
                "Venduto {$description} a {$purchaser->name}",
                $locked->price,
                $actor,
            );

            // Il venditore non è al computer quando qualcuno compra: senza
            // avviso scoprirebbe l'oro solo per caso.
            $seller->user()->first()?->notify(new ListingSold($locked, $purchaser->name));

            return $locked;
        });
    }
}
