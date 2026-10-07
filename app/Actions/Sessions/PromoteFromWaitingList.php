<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Notifications\SessionSeatConfirmed;
use Illuminate\Support\Facades\DB;

/**
 * Il DM chiama qualcuno dalla lista d'attesa, giocatore o ospite, quando un
 * posto si libera. A sessione già confermata il posto nasce confermato.
 */
final class PromoteFromWaitingList
{
    public function handle(GameSession $session, SessionBooking $booking): SeatStatus
    {
        return DB::transaction(function () use ($session, $booking) {
            $locked = GameSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $posto = SessionBooking::whereKey($booking->getKey())->where('game_session_id', $locked->getKey())->firstOrFail();

            if (! $locked->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            if ($posto->status !== SeatStatus::Waiting) {
                throw SessionUnavailableException::notWaiting();
            }

            if ($locked->isFull()) {
                throw SessionUnavailableException::full();
            }

            $nuovo = $locked->isConfirmed() ? SeatStatus::Confirmed : SeatStatus::Booked;
            $posto->forceFill(['status' => $nuovo, 'decided_at' => now()])->save();

            if ($nuovo === SeatStatus::Confirmed) {
                $posto->user?->notify(new SessionSeatConfirmed($locked));
            }

            return $nuovo;
        });
    }
}
