<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

/**
 * Avvisa chi può approvare — DM e admin — che c'è qualcosa da decidere nel
 * pannello. Esclude chi ha proposto: non si decide sulla propria richiesta.
 */
final class AnnounceForApproval
{
    public function handle(InAppNotification $notification, ?User $tranne = null): void
    {
        $approvatori = User::query()
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', [User::ROLE_DM, User::ROLE_ADMIN]))
            ->when($tranne, fn (Builder $q) => $q->whereKeyNot($tranne->getKey()))
            ->get();

        Notification::send($approvatori, $notification);
    }
}
