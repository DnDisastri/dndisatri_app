<?php

namespace App\Policies;

use App\Models\Subrace;
use App\Models\User;

/**
 * Il catalogo delle sottorazze lo curano DM e admin. Vale per tutti i
 * personaggi del gruppo, quindi non è materia da giocatori.
 *
 * Le sottorazze da manuale non si cancellano: ci sono personaggi che le
 * hanno, e toglierle dall'elenco vorrebbe dire non poterle più scegliere per
 * un capriccio di chi passa di lì. Le homebrew sì, le ha aggiunte qualcuno di
 * qui e qualcuno di qui può ripensarci.
 */
class SubracePolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Subrace $subrace): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Subrace $subrace): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, Subrace $subrace): bool
    {
        return $this->conduce($user) && $subrace->is_homebrew;
    }
}
