<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

/** Qualsiasi DM, anche chi copre un collega. I giocatori vedono solo i propri PF che cambiano. */
class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDm();
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $user->isDm();
    }

    public function create(User $user): bool
    {
        return $user->isDm();
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->isDm();
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        return $user->isDm();
    }
}
