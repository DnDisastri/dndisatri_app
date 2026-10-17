<?php

namespace App\Policies;

use App\Models\Feat;
use App\Models\User;

/** Come le sottospecie: lo curano DM e admin, e quelli del manuale non si cancellano. */
class FeatPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Feat $feat): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Feat $feat): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, Feat $feat): bool
    {
        return $this->conduce($user) && $feat->is_homebrew;
    }
}
