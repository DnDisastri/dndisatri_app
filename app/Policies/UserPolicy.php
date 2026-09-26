<?php

namespace App\Policies;

use App\Models\User;

/**
 * I DM possono consultare gli utenti (e le loro schede); creare, modificare e
 * cambiare ruoli resta solo degli admin. È lì il buco da tenere chiuso.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isDm();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->isDm();
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
