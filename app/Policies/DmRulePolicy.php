<?php

namespace App\Policies;

use App\Models\DmRule;
use App\Models\User;

/** Le leggono DM e admin, le scrivono gli admin. */
class DmRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function view(User $user, DmRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DmRule $rule): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, DmRule $rule): bool
    {
        return $user->isAdmin();
    }
}
