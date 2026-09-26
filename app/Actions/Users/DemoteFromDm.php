<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\Campaign;
use App\Models\User;
use App\Notifications\DmRoleRevoked;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Riporta un dungeon master a giocatore.
 *
 * Il contrario di `PromoteToDm`, e il motivo per cui esiste: una nomina che si
 * dà dal pannello e si toglie solo dal database sarebbe una porta che si apre
 * e non si chiude.
 *
 * **Non si toglie a chi ha un tavolo aperto.** Una campagna senza un master
 * che possa condurla resterebbe in piedi ma inutilizzabile, e il danno
 * comparirebbe più tardi, addosso ai giocatori. Prima si chiude la campagna o
 * si passa a un altro, poi si toglie il ruolo.
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
