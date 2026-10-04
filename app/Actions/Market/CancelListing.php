<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Enums\ListingStatus;
use App\Exceptions\MarketException;
use App\Models\MarketListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** L'altra metà di `CreateListing`: l'oggetto in deposito torna al venditore. */
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
                valueCp: $locked->unit_value_cp,
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
