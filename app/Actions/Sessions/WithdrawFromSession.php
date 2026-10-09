<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\SessionBooking;
use App\Notifications\SessionSeatReleased;

/**
 * Ritirarsi, anche col posto confermato: chi si ammala non viene lo stesso.
 * La riga resta, con lo stato cambiato. Se si libera un posto vero, lo sanno
 * il DM della campagna e gli admin; nessuno entra da solo al suo posto.
 */
final class WithdrawFromSession
{
    public function __construct(private readonly NotifySeatReleased $notifyReleased) {}

    public function handle(SessionBooking $posto): void
    {
        if (! $posto->session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        if (! $posto->status->isActive()) {
            throw SessionUnavailableException::notAParticipant();
        }

        $liberaUnPosto = $posto->status->takesSeat();

        $posto->forceFill([
            'status' => SeatStatus::Withdrawn,
            'decided_at' => now(),
            'offer_expires_at' => null,
        ])->save();

        if ($liberaUnPosto) {
            $this->notifyReleased->handle($posto, SessionSeatReleased::WITHDRAWN);
        }
    }
}
