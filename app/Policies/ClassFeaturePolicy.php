<?php

namespace App\Policies;

use App\Models\ClassFeature;
use App\Models\User;

/** Il catalogo lo curano DM e admin: le schede li leggono, gli usi spesi restano sul personaggio. */
class ClassFeaturePolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, ClassFeature $item): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, ClassFeature $item): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, ClassFeature $item): bool
    {
        return $this->conduce($user);
    }
}
