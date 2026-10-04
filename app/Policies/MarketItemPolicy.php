<?php

namespace App\Policies;

use App\Models\MarketItem;
use App\Models\User;

/**
 * Il catalogo del negozio lo gestiscono **solo gli admin**.
 *
 * È un cambiamento rispetto al brief, dove era dei DM: con prezzi e scorte in
 * mano a due persone sole l'economia del gruppo resta coerente (decisione D1).
 * Unica eccezione, `putOnSale`: il prezzo degli oggetti arrivati in baratto.
 */
class MarketItemPolicy
{
    /** Il negozio lo guardano tutti: è lì per comprare. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MarketItem $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MarketItem $item): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MarketItem $item): bool
    {
        return $user->isAdmin();
    }

    /** Gli oggetti arrivati in baratto: il prezzo lo decide anche un DM, come concordato. */
    public function putOnSale(User $user, MarketItem $item): bool
    {
        return $item->in_storage && ($user->isAdmin() || $user->isDm());
    }
}
