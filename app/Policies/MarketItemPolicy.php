<?php

namespace App\Policies;

use App\Models\MarketItem;
use App\Models\User;

/**
 * Il catalogo del negozio lo gestiscono **solo gli admin**.
 *
 * È un cambiamento rispetto al brief, dove era dei DM: con prezzi e scorte in
 * mano a due persone sole l'economia del gruppo resta coerente (decisione D1).
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
}
