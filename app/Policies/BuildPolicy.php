<?php

namespace App\Policies;

use App\Models\Build;
use App\Models\User;

/**
 * Le build consigliate le scrivono **i dungeon master**, non solo gli admin.
 *
 * È la differenza con news ed eventi, che restano della redazione: una build è
 * consiglio di gioco, e chi conduce le serate è esattamente la persona che sa
 * quale personaggio funziona al proprio tavolo.
 *
 * Ognuno però risponde di quello che scrive: un DM modifica le proprie, gli
 * admin tutte.
 */
class BuildPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Una bozza la vede solo chi può metterci mano. */
    public function view(User $user, Build $build): bool
    {
        return $build->isPublished() || $this->canEdit($user, $build);
    }

    public function create(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function update(User $user, Build $build): bool
    {
        return $this->canEdit($user, $build);
    }

    /**
     * Cancellare no: una build a cui qualcuno si è ispirato è un pezzo di
     * storia del gruppo. Si toglie dalla pubblicazione e resta lì.
     */
    public function delete(User $user, Build $build): bool
    {
        return $user->isAdmin();
    }

    /**
     * Le otto arrivate dalla vecchia applicazione **non hanno un autore**:
     * sono del gruppo, e le completa qualsiasi DM. Senza questa riga sarebbero
     * proprio quelle da riempire a restare intoccabili.
     */
    private function canEdit(User $user, Build $build): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDm()
            && ($build->created_by === null || $build->created_by === $user->getKey());
    }
}
