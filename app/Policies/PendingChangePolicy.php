<?php

namespace App\Policies;

use App\Models\PendingChange;
use App\Models\User;

/**
 * La bacheca è condivisa fra tutti i DM e tutti gli admin (decisione D1): la
 * richiesta la chiude il primo che arriva, e chi ha deciso resta tracciato.
 *
 * `approve` e `reject` NON passano dal Gate::before degli admin, perché hanno
 * la regola del conflitto di interessi (vedi AppServiceProvider).
 */
class PendingChangePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Il proponente segue la sua richiesta; DM e admin vedono tutto. */
    public function view(User $user, PendingChange $change): bool
    {
        return $change->requested_by === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Chi ha deciso lo vedono solo DM e admin. Il giocatore vede l'esito e
     * basta: gli admin non compaiono mai davanti ai giocatori, e mostrare il
     * nome solo per i DM darebbe un elenco a metà.
     */
    public function viewReviewer(User $user, PendingChange $change): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function approve(User $user, PendingChange $change): bool
    {
        return $change->isPending() && $this->canDecide($user, $change);
    }

    public function reject(User $user, PendingChange $change): bool
    {
        return $change->isPending() && $this->canDecide($user, $change);
    }

    /** Il proponente può ritirare la propria richiesta finché è aperta. */
    public function cancel(User $user, PendingChange $change): bool
    {
        return $change->isPending() && $change->requested_by === $user->getKey();
    }

    public function delete(User $user, PendingChange $change): bool
    {
        return false;
    }

    /**
     * Decide chi ha un ruolo di gestione, purché la richiesta non riguardi un
     * personaggio suo: un DM è anche un giocatore, e non si approva da solo.
     */
    private function canDecide(User $user, PendingChange $change): bool
    {
        if (! $user->isDm() && ! $user->isAdmin()) {
            return false;
        }

        return $change->character->user_id !== $user->getKey();
    }
}
