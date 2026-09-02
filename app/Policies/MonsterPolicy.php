<?php

namespace App\Policies;

use App\Models\Monster;
use App\Models\User;

/**
 * Il bestiario è per chi conduce; i giocatori non lo toccano. Un mostro
 * pubblico è condiviso fra tutti i DM; uno legato a una campagna lo gestisce
 * solo il suo DM (l'admin sempre).
 */
class MonsterPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    /** Pubblico, oppure della campagna condotta da questo utente. */
    private function raggiungibile(User $user, Monster $monster): bool
    {
        return $user->isAdmin()
            || $monster->isPublic()
            || $monster->campaign?->dm_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Monster $monster): bool
    {
        return $this->conduce($user) && $this->raggiungibile($user, $monster);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Monster $monster): bool
    {
        return $this->conduce($user) && $this->raggiungibile($user, $monster);
    }

    public function delete(User $user, Monster $monster): bool
    {
        return $this->conduce($user) && $this->raggiungibile($user, $monster);
    }
}
