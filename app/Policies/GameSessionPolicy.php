<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\User;

/**
 * Le sessioni le organizza il DM del tavolo, più gli admin.
 *
 * I recap invece li legge **tutto il gruppo**: il mondo è condiviso e le
 * vicende di un tavolo interessano anche gli altri.
 */
class GameSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GameSession $session): bool
    {
        return true;
    }

    /** La campagna è opzionale: vedi la nota in QuestPolicy::create(). */
    public function create(User $user, ?Campaign $campaign = null): bool
    {
        if ($campaign === null) {
            return $user->isAdmin() || $user->isDm();
        }

        return $campaign->isActive() && $this->runsTheTable($user, $campaign);
    }

    public function update(User $user, GameSession $session): bool
    {
        return $this->runsTheTable($user, $session->campaign);
    }

    /**
     * Scrivere il resoconto. Resta possibile anche su una campagna conclusa:
     * i recap si scrivono dopo, e a volte molto dopo.
     */
    public function writeRecap(User $user, GameSession $session): bool
    {
        return $this->runsTheTable($user, $session->campaign);
    }

    /** Segnare i presenti a fine serata. */
    public function recordAttendance(User $user, GameSession $session): bool
    {
        return $this->runsTheTable($user, $session->campaign);
    }

    public function delete(User $user, GameSession $session): bool
    {
        return $this->runsTheTable($user, $session->campaign);
    }

    private function runsTheTable(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin()
            || ($user->isDm() && $campaign->dm_id === $user->getKey());
    }
}
