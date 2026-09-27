<?php

namespace App\Policies;

use App\Models\Subclass;
use App\Models\User;

/**
 * Il catalogo delle sottoclassi lo curano DM e admin. Vale per tutti i
 * personaggi del gruppo, quindi non è materia da giocatori.
 *
 * Le sottoclassi da manuale non si cancellano: ci sono personaggi che le
 * hanno, e toglierle dall'elenco vorrebbe dire non poterle più scegliere per
 * un capriccio di chi passa di lì. Le homebrew sì, le ha aggiunte qualcuno di
 * qui e qualcuno di qui può ripensarci.
 */
class SubclassPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Subclass $subclass): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Subclass $subclass): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, Subclass $subclass): bool
    {
        return $this->conduce($user) && $subclass->is_homebrew;
    }
}
