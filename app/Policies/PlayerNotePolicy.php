<?php

namespace App\Policies;

use App\Models\PlayerNote;
use App\Models\User;

class PlayerNotePolicy
{
    /** Le note su un giocatore: le leggono i DM e gli admin, mai lui. */
    public function viewAbout(User $user, User $player): bool
    {
        return ($user->isDm() || $user->isAdmin()) && ! $user->is($player);
    }

    public function delete(User $user, PlayerNote $note): bool
    {
        return $user->isAdmin() || $note->author_id === $user->getKey();
    }
}
