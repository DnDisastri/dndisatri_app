<?php

namespace App\Policies;

use App\Enums\TradeStatus;
use App\Models\Trade;
use App\Models\User;

class TradePolicy
{
    /** Uno scambio lo vedono le due parti, più DM e admin dal pannello. */
    public function view(User $user, Trade $trade): bool
    {
        return $this->isProposer($user, $trade)
            || $this->isRecipient($user, $trade)
            || $user->isDm()
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin() && $user->characters()->alive()->exists();
    }

    /** Accetta e rifiuta solo il destinatario. */
    public function accept(User $user, Trade $trade): bool
    {
        return $trade->isOpen() && $this->isRecipient($user, $trade);
    }

    public function reject(User $user, Trade $trade): bool
    {
        return $trade->isOpen() && $this->isRecipient($user, $trade);
    }

    /** Ritira la proposta chi l'ha fatta, o un admin per fermarla. */
    public function cancel(User $user, Trade $trade): bool
    {
        return $trade->isOpen() && ($this->isProposer($user, $trade) || $user->isAdmin());
    }

    /**
     * Annullare uno scambio **già concluso** è cosa da soli admin (D12).
     *
     * Non è la stessa cosa di ritirarne uno aperto: lì non era ancora successo
     * niente, qui si va a rimettere le mani in due inventari.
     */
    public function reverse(User $user, Trade $trade): bool
    {
        return $user->isAdmin()
            && $trade->status === TradeStatus::Accepted
            && $trade->reversed_at === null;
    }

    private function isProposer(User $user, Trade $trade): bool
    {
        return $user->characters()->whereKey($trade->from_character_id)->exists();
    }

    private function isRecipient(User $user, Trade $trade): bool
    {
        return $user->characters()->whereKey($trade->to_character_id)->exists();
    }
}
