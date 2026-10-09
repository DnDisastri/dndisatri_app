<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\User;

/**
 * Le sessioni le crea e modifica il DM della campagna, o un admin. Condurle
 * (posti, ospiti, presenze, resoconto) lo può qualsiasi DM. Le leggono tutti.
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
     * Scrivere il resoconto: qualsiasi DM, perché chi copre un collega deve poter
     * chiudere la sessione. Resta possibile anche su una campagna conclusa: i recap
     * si scrivono dopo, e a volte molto dopo.
     */
    public function writeRecap(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $user->isDm();
    }

    /** Segnare i presenti e dare le ricompense di fine sessione: come il resoconto. */
    public function recordAttendance(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $user->isDm();
    }

    /** Chiedere un posto: chi non conduce quella campagna, anche a sessione piena. */
    public function book(User $user, GameSession $session): bool
    {
        return $session->acceptsBookings()
            && $session->campaign?->dm_id !== $user->getKey()
            && ! $session->hasParticipant($user);
    }

    public function withdraw(User $user, GameSession $session): bool
    {
        return $session->acceptsBookings() && $session->hasParticipant($user);
    }

    /** Vedere tutte le richieste, coi contatti, e offrire i posti: qualsiasi DM, come chiudere la sessione. */
    public function manageSeats(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $user->isDm();
    }

    /** Un ospite senza account, prenotato fuori dall'app: qualsiasi DM, in qualsiasi campagna. */
    public function addGuest(User $user, GameSession $session): bool
    {
        return ($user->isAdmin() || $user->isDm()) && $session->acceptsBookings();
    }

    /** Togliere un ospite o collegarlo all'account con cui si è registrato. */
    public function manageGuests(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $user->isDm();
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
