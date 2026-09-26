<?php

namespace App\Policies;

use App\Models\SupervisedAction;
use App\Models\User;

/**
 * Chi vigila sulle azioni di un giocatore richiamato (D13).
 *
 * Stessa forma della bacheca delle richieste, e per le stesse ragioni: decide
 * chi conduce, e nessuno decide su sé stesso.
 */
class SupervisedActionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Il richiedente segue la propria; DM e admin vedono tutto. */
    public function view(User $user, SupervisedAction $action): bool
    {
        return $action->user_id === $user->getKey() || $user->isDm() || $user->isAdmin();
    }

    public function approve(User $user, SupervisedAction $action): bool
    {
        return $action->isPending() && $this->canDecide($user, $action);
    }

    public function reject(User $user, SupervisedAction $action): bool
    {
        return $action->isPending() && $this->canDecide($user, $action);
    }

    /** Chi l'ha chiesta può ritirarla finché è aperta. */
    public function cancel(User $user, SupervisedAction $action): bool
    {
        return $action->isPending() && $action->user_id === $user->getKey();
    }

    public function delete(User $user, SupervisedAction $action): bool
    {
        return false;
    }

    /**
     * Decide chi conduce, purché non abbia un piede nell'operazione.
     *
     * Qui il conflitto d'interessi è più largo che in bacheca: non basta che
     * non sia una sua richiesta, deve non esserci **nessun suo personaggio**
     * dentro lo scambio. Chi vende dall'altro lato non può essere anche
     * l'arbitro.
     */
    private function canDecide(User $user, SupervisedAction $action): bool
    {
        if (! $user->isDm() && ! $user->isAdmin()) {
            return false;
        }

        if ($action->user_id === $user->getKey()) {
            return false;
        }

        $involved = $action->involvedCharacterIds();

        return $involved === []
            || ! $user->characters()->whereIn('id', $involved)->exists();
    }
}
