<?php

namespace App\Policies;

use App\Models\Map;
use App\Models\User;

/**
 * Le mappe le vedono tutti; le carica chi conduce.
 *
 * Una mappa legata a una campagna la gestisce il DM di quel tavolo, come le
 * quest e le sessioni. Una mappa generale, senza campagna, la può caricare
 * qualsiasi DM: vale per tutto il gruppo e non è materiale di nessuno in
 * particolare.
 */
class MapPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Map $map): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function update(User $user, Map $map): bool
    {
        return $this->manages($user, $map);
    }

    public function delete(User $user, Map $map): bool
    {
        return $this->manages($user, $map);
    }

    private function manages(User $user, Map $map): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isDm()) {
            return false;
        }

        // Mappa generale: qualsiasi DM. Mappa di un tavolo: il suo DM.
        return $map->isGeneral() || $map->campaign?->dm_id === $user->getKey();
    }
}
