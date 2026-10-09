<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\Campaign;
use App\Models\User;
use App\Notifications\DmRoleRevoked;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Riporta un dungeon master a giocatore, il contrario di `PromoteToDm`.
 *
 * Non a chi conduce una campagna aperta: resterebbe senza DM. Prima la si
 * chiude o la si passa a un altro.
 */
final class DemoteFromDm
{
    public function handle(User $target, User $admin): User
    {
        if (! $admin->isAdmin()) {
            throw new RuntimeException('Solo un amministratore può togliere il ruolo di dungeon master.');
        }

        return DB::transaction(function () use ($target, $admin) {
            $locked = User::whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isDm()) {
                throw new RuntimeException("{$locked->name} non è un dungeon master.");
            }

            $aperte = Campaign::active()->runBy($locked)->pluck('title');

            if ($aperte->isNotEmpty()) {
                throw new RuntimeException(
                    "{$locked->name} conduce ancora: {$aperte->join(', ', ' e ')}. ".
                    'Chiudi la campagna o assegnala a un altro dungeon master.'
                );
            }

            $locked->removeRole(User::ROLE_DM);

            activity('ruoli')
                ->performedOn($locked)
                ->causedBy($admin)
                ->log('Tolto il ruolo di dungeon master');

            $locked->notify(new DmRoleRevoked);

            return $locked->refresh();
        });
    }
}
