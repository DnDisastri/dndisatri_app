<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Enums\ListingStatus;
use App\Exceptions\MarketException;
use App\Models\MarketListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ritira un annuncio: l'oggetto torna nell'inventario del venditore.
 *
 * È l'altra metà della regola di `CreateListing`: se l'oggetto esce subito
 * dall'inventario, deve esistere una strada certa per farlo rientrare, o un
 * annuncio annullato lo farebbe sparire.
 */
final class CancelListing
{
    public function handle(MarketListing $listing, ?User $actor = null): MarketListing
    {
        return DB::transaction(function () use ($listing, $actor) {
            $locked = MarketListing::whereKey($listing->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new MarketException('Questo annuncio è già stato chiuso.');
            }

            $seller = $locked->seller()->lockForUpdate()->firstOrFail();

            $seller->addToInventory(
                name: $locked->name,
                qty: $locked->qty,
                category: $locked->category,
                value: $locked->unit_value,
                details: $locked->details,
            );

            $locked->forceFill([
                'status' => ListingStatus::Cancelled,
                'resolved_at' => now(),
            ])->save();

            $seller->recordInLedger(
                LedgerAction::ListingCancelled,
                "Ritirato dalla vendita {$locked->qty}× {$locked->name}",
                actor: $actor,
            );

            return $locked;
        });
    }
}
