<?php

namespace App\Policies;

use App\Models\Background;
use App\Models\User;

/** Il catalogo lo curano DM e admin; quello che qualcuno usa non si cancella. */
class BackgroundPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Background $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Background $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, Background $item): bool
    {
        return $this->conduce($user) && ! $item->isInUse();
    }
}
