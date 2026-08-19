<?php

namespace App\Policies;

use App\Models\MarketListing;
use App\Models\User;

/**
 * Il mercato fra giocatori è roba loro: nessuna approvazione, ma ogni
 * movimento finisce nel Registro.
 */
class MarketListingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MarketListing $listing): bool
    {
        return true;
    }

    /** Vende chi ha un personaggio vivo. Gli admin non giocano. */
    public function create(User $user): bool
    {
        return ! $user->isAdmin() && $user->characters()->alive()->exists();
    }

    /**
     * Compra chiunque abbia un personaggio vivo, tranne il venditore stesso:
     * comprarsi il proprio annuncio non avrebbe senso e falserebbe il Registro.
     */
    public function buy(User $user, MarketListing $listing): bool
    {
        if (! $listing->isOpen() || $user->isAdmin()) {
            return false;
        }

        return $user->characters()->alive()->exists()
            && ! $this->owns($user, $listing);
    }

    /** Ritira l'annuncio chi l'ha messo, o un admin per fare pulizia. */
    public function cancel(User $user, MarketListing $listing): bool
    {
        return $listing->isOpen() && ($this->owns($user, $listing) || $user->isAdmin());
    }

    /**
     * Annullare una vendita **già avvenuta**: solo admin (D12).
     *
     * Ritirare un annuncio aperto è un'altra cosa, e sta in `cancel()`: lì non
     * era ancora successo niente.
     */
    public function reverse(User $user, MarketListing $listing): bool
    {
        return $user->isAdmin()
            && $listing->buyer_character_id !== null
            && $listing->reversed_at === null;
    }

    private function owns(User $user, MarketListing $listing): bool
    {
        return $user->characters()->whereKey($listing->seller_character_id)->exists();
    }
}
