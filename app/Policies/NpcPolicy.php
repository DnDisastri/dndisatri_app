<?php

namespace App\Policies;

use App\Models\Npc;
use App\Models\User;

/** Qualsiasi DM; i giocatori non li vedono mai. */
class NpcPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDm();
    }

    public function view(User $user, Npc $npc): bool
    {
        return $user->isDm();
    }

    public function create(User $user): bool
    {
        return $user->isDm();
    }

    public function update(User $user, Npc $npc): bool
    {
        return $user->isDm();
    }

    public function delete(User $user, Npc $npc): bool
    {
        return $user->isDm();
    }
}
