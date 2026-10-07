<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warning;

/**
 * I richiami e il loro storico: DM e admin (D13).
 */
class WarningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    /** Il richiamato vede i propri: sa già di averli. */
    public function view(User $user, Warning $warning): bool
    {
        return $user->isDm() || $user->isAdmin() || $warning->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function lift(User $user, Warning $warning): bool
    {
        return ($user->isDm() || $user->isAdmin()) && $warning->isActive();
    }

    /** Un richiamo non si cancella: si toglie, e resta nello storico. */
    public function delete(User $user, Warning $warning): bool
    {
        return false;
    }
}
