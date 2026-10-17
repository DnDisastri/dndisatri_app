<?php

namespace App\Policies;

use App\Models\GameClass;
use App\Models\User;

/** Il catalogo lo curano DM e admin; quella che qualcuno usa non si cancella. */
class GameClassPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, GameClass $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, GameClass $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, GameClass $item): bool
    {
        return $this->conduce($user) && ! $item->isInUse();
    }
}
