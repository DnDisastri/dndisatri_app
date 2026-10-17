<?php

namespace App\Policies;

use App\Models\MagicItem;
use App\Models\User;

/** Il catalogo lo curano DM e admin; gli oggetti già dati restano: sono copie. */
class MagicItemPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, MagicItem $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, MagicItem $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, MagicItem $item): bool
    {
        return $this->conduce($user);
    }
}
