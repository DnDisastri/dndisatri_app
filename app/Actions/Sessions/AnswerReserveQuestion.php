<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\SessionBooking;

/** «Resti fra le riserve?»: sì diventa riserva, no ritira la richiesta. */
final class AnswerReserveQuestion
{
    public function handle(SessionBooking $posto, bool $resta): SeatStatus
    {
        if (! $posto->session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        if (! $posto->awaitsReserveAnswer()) {
            throw SessionUnavailableException::noReserveQuestion();
        }

        $posto->forceFill([
            'status' => $resta ? SeatStatus::Reserve : SeatStatus::Withdrawn,
            'decided_at' => now(),
        ])->save();

        return $posto->status;
    }
}
