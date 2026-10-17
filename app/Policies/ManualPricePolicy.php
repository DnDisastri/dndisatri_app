<?php

namespace App\Policies;

use App\Models\ManualPrice;
use App\Models\User;

/** Il catalogo lo curano DM e admin; il listino non tocca i prezzi veri dell.Emporio. */
class ManualPricePolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, ManualPrice $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, ManualPrice $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, ManualPrice $item): bool
    {
        return $this->conduce($user);
    }
}
