<?php

namespace App\Policies;

use App\Models\TradeRequest;
use App\Models\User;

/**
 * Chi può fare cosa con una richiesta di scambio.
 *
 * Le stesse regole degli scambi, con una differenza che conta: qui **non c'è
 * un `reverse`**. Una richiesta non muove niente, quindi non c'è niente da
 * rimettere a posto — e quando diventa uno scambio, ad annullarlo si va sullo
 * scambio, dove le cose sono davvero successe.
 */
class TradeRequestPolicy
{
    /** La vedono le due parti, più DM e admin dal pannello. */
    public function view(User $user, TradeRequest $request): bool
    {
        return $this->isAsker($user, $request)
            || $this->isRecipient($user, $request)
            || $user->isDm()
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin() && $user->characters()->alive()->exists();
    }

    /**
     * Risponde solo chi l'ha ricevuta: è l'unico che sa se quell'oggetto ce
     * l'ha davvero, ed è tutta la ragione per cui la richiesta esiste.
     */
    public function accept(User $user, TradeRequest $request): bool
    {
        return $request->isOpen() && $this->isRecipient($user, $request);
    }

    public function reject(User $user, TradeRequest $request): bool
    {
        return $request->isOpen() && $this->isRecipient($user, $request);
    }

    /** Ritira la richiesta chi l'ha fatta, o un admin per fermarla. */
    public function cancel(User $user, TradeRequest $request): bool
    {
        return $request->isOpen() && ($this->isAsker($user, $request) || $user->isAdmin());
    }

    private function isAsker(User $user, TradeRequest $request): bool
    {
        return $user->characters()->whereKey($request->from_character_id)->exists();
    }

    private function isRecipient(User $user, TradeRequest $request): bool
    {
        return $user->characters()->whereKey($request->to_character_id)->exists();
    }
}
