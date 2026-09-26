<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Avvisa i giocatori di una novità del gruppo (un evento, un nuovo tavolo).
 *
 * Una sola volta per record: `players_notified_at` fa da guardia, così
 * modificare più tardi lo stesso evento non rimanda l'avviso.
 */
final class AnnounceToPlayers
{
    public function handle(Model $record, InAppNotification $notification, ?User $tranne = null): void
    {
        if ($record->players_notified_at !== null) {
            return;
        }

        $giocatori = User::query()
            ->whereNotNull('approved_at')
            ->whereHas('roles', fn (Builder $q) => $q->where('name', User::ROLE_PLAYER))
            ->when($tranne, fn (Builder $q) => $q->whereKeyNot($tranne->getKey()))
            ->get();

        Notification::send($giocatori, $notification);

        // saveQuietly: non è una modifica di contenuto, non va nel log attività.
        $record->forceFill(['players_notified_at' => now()])->saveQuietly();
    }
}
