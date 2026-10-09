<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

/**
 * Le campagne non danno poteri sui personaggi (D1). Le gestisce il loro DM,
 * e gli admin.
 */
class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return true;
    }

    /** Un DM apre le proprie campagne; un admin anche per conto di altri. */
    public function create(User $user): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin() || $this->owns($user, $campaign);
    }

    /** Chiudere una campagna è irreversibile. */
    public function end(User $user, Campaign $campaign): bool
    {
        return ($user->isAdmin() || $this->owns($user, $campaign)) && $campaign->isActive();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, Campaign $campaign): bool
    {
        return $user->isDm() && $campaign->dm_id === $user->getKey();
    }
}
