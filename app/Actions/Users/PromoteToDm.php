<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;
use App\Models\User;
use App\Notifications\DmRoleGranted;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Promuove un giocatore a dungeon master, per decisione di un admin.
 *
 * L'altra strada esiste già ed è `ReviewDmRequest`: il giocatore chiede, un
 * admin approva. Questa serve al caso opposto, quando è l'admin a proporlo, e
 * risparmia la finzione di far presentare una domanda a chi non l'ha chiesta.
 *
 * Il ruolo resta assegnabile **solo lato server**, da qui o da lì: non è un
 * campo scrivibile da nessun form. Era il buco della vecchia applicazione.
 */
final class PromoteToDm
{
    public function handle(User $target, User $promoter): User
    {
        if (! $promoter->isAdmin()) {
            throw new RuntimeException('Solo un amministratore può nominare un dungeon master.');
        }

        if ($target->isAdmin()) {
            throw new RuntimeException('Gli amministratori non conducono: non hanno personaggi né tavoli.');
        }

        return DB::transaction(function () use ($target, $promoter) {
            $locked = User::whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDm()) {
                throw new RuntimeException("{$locked->name} è già un dungeon master.");
            }

            $locked->assignRole(Role::findOrCreate(User::ROLE_DM, 'web'));

            /*
             * Se aveva una domanda aperta, va chiusa qui. Lasciarla in sospeso
             * significherebbe un badge nel pannello che invita a decidere su
             * qualcuno che è già stato nominato.
             */
            DmRequest::where('user_id', $locked->getKey())->pending()->update([
                'status' => PendingChangeStatus::Approved,
                'reviewed_by' => $promoter->getKey(),
                'reviewed_at' => now(),
                'review_note' => 'Nominato direttamente da un amministratore.',
            ]);

            activity('ruoli')
                ->performedOn($locked)
                ->causedBy($promoter)
                ->log('Nominato dungeon master');

            $locked->notify(new DmRoleGranted);

            return $locked->refresh();
        });
    }
}
