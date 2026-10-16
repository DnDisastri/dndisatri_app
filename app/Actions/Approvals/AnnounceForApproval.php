<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\GameSession;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

/**
 * Avvisa chi può approvare che c'è qualcosa da decidere nel pannello. Esclude
 * chi ha proposto: non si decide sulla propria richiesta.
 *
 * Di norma sono DM e admin insieme. Chi decide da solo lo dice: le iscrizioni,
 * per esempio, le approvano i soli amministratori.
 *
 * Con la sessione da cui arriva la richiesta, i DM sono solo quelli che l'hanno
 * condotta; gli admin restano. Senza DM segnati, tutti come prima.
 */
final class AnnounceForApproval
{
    /** @param  list<string>  $ruoli */
    public function handle(
        InAppNotification $notification,
        ?User $tranne = null,
        array $ruoli = [User::ROLE_DM, User::ROLE_ADMIN],
        ?GameSession $session = null,
    ): void {
        $conducono = $session?->conductors()->pluck('id')->all() ?? [];

        $approvatori = User::query()
            ->when(
                $conducono !== [] && in_array(User::ROLE_DM, $ruoli, true),
                fn (Builder $q) => $q->where(fn (Builder $q) => $q
                    ->whereKey($conducono)
                    ->orWhereHas('roles', fn (Builder $q) => $q->whereIn('name', array_diff($ruoli, [User::ROLE_DM])))),
                fn (Builder $q) => $q->whereHas('roles', fn (Builder $q) => $q->whereIn('name', $ruoli)),
            )
            ->when($tranne, fn (Builder $q) => $q->whereKeyNot($tranne->getKey()))
            ->get();

        Notification::send($approvatori, $notification);
    }
}
