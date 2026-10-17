<?php

namespace App\Policies;

use App\Models\BaseItem;
use App\Models\User;

/** Il catalogo lo curano DM e admin; quello che uno zaino o l'Emporio usa non si cancella. */
class BaseItemPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, BaseItem $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, BaseItem $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, BaseItem $item): bool
    {
        return $this->conduce($user) && ! $item->isInUse();
    }
}
