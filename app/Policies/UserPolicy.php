<?php

namespace App\Policies;

use App\Models\User;

/**
 * La gestione degli account è solo degli admin.
 *
 * Senza questa policy Filament lascerebbe entrare chiunque acceda al pannello,
 * DM compresi, e da lì si arriva a cambiare i ruoli: esattamente il buco che
 * la riscrittura doveva chiudere.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /** Nessuno cancella account dal pannello: si perderebbero personaggi e Registro. */
    public function delete(User $user, User $target): bool
    {
        return false;
    }
}
