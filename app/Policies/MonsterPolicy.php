<?php

namespace App\Policies;

use App\Models\Monster;
use App\Models\User;

/**
 * Il bestiario è **condiviso**: un repertorio di mostri serve a tutti i tavoli,
 * e chiunque conduca lo arricchisce e lo corregge. Niente proprietà per riga —
 * a differenza delle build, un mostro non è il consiglio di qualcuno, è un dato
 * di gioco. I giocatori non lo toccano.
 */
class MonsterPolicy
{
    private function conduce(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->conduce($user);
    }

    public function view(User $user, Monster $monster): bool
    {
        return $this->conduce($user);
    }

    public function create(User $user): bool
    {
        return $this->conduce($user);
    }

    public function update(User $user, Monster $monster): bool
    {
        return $this->conduce($user);
    }

    public function delete(User $user, Monster $monster): bool
    {
        return $this->conduce($user);
    }
}
