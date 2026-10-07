<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\User;

/**
 * Ritirarsi, anche a sessione confermata: chi si ammala non viene lo stesso.
 * La riga resta, con lo stato cambiato.
 */
final class WithdrawFromSession
{
    public function handle(GameSession $session, User $user): void
    {
        if (! $session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        if (! $session->hasParticipant($user)) {
            throw SessionUnavailableException::notAParticipant();
        }

        $session->players()->updateExistingPivot($user, [
            'status' => SeatStatus::Withdrawn->value,
            'decided_at' => now(),
        ]);
    }
}
