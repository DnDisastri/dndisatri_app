<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;

/**
 * Le quest le gestisce il DM della campagna, o un admin: a differenza dei
 * personaggi (D1), qui conta la campagna.
 */
class QuestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quest $quest): bool
    {
        return true;
    }

    /** Senza campagna (Filament chiede prima di saperla) basta il ruolo. */
    public function create(User $user, ?Campaign $campaign = null): bool
    {
        if ($campaign === null) {
            return $user->isAdmin() || $user->isDm();
        }

        return $campaign->isActive() && $this->runsTheTable($user, $campaign);
    }

    public function update(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $this->runsTheTable($user, $quest->campaign);
    }

    /** Completare o chiudere: irreversibile, e solo se ancora attiva. */
    public function conclude(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $this->runsTheTable($user, $quest->campaign);
    }

    /** «Mi interessa»: non è una prenotazione, ci si prenota alla sessione. */
    public function interest(User $user, Quest $quest): bool
    {
        return $quest->isActive();
    }

    /** Mettere la quest in una sessione, o toglierla. */
    public function schedule(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $this->runsTheTable($user, $quest->campaign);
    }

    public function delete(User $user, Quest $quest): bool
    {
        return $user->isAdmin();
    }

    private function runsTheTable(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin()
            || ($user->isDm() && $campaign->dm_id === $user->getKey());
    }
}
