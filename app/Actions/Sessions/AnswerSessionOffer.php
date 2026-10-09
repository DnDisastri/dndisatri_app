<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\SessionBooking;
use App\Notifications\SessionSeatReleased;
use Illuminate\Support\Facades\DB;

/**
 * Il giocatore, o l'ospite dal suo link, conferma il posto offerto o ci
 * rinuncia. Dopo una conferma che riempie la sessione, agli altri si chiede
 * se restano come riserve.
 */
final class AnswerSessionOffer
{
    public function __construct(
        private readonly AskReserves $askReserves,
        private readonly NotifySeatReleased $notifyReleased,
    ) {}

    public function handle(SessionBooking $booking, bool $accetta): SeatStatus
    {
        $posto = DB::transaction(function () use ($booking, $accetta) {
            $posto = SessionBooking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $posto->session->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            // Anche un'offerta non ancora segnata scaduta dal comando, se l'ora è passata, non vale più.
            if ($posto->status !== SeatStatus::Offered || $posto->offer_expires_at?->isPast()) {
                throw SessionUnavailableException::noOffer();
            }

            $posto->forceFill([
                'status' => $accetta ? SeatStatus::Confirmed : SeatStatus::Withdrawn,
                'decided_at' => now(),
                'offer_expires_at' => null,
            ])->save();

            return $posto;
        });

        if ($accetta) {
            $this->askReserves->handle($posto->session);
        } else {
            $this->notifyReleased->handle($posto, SessionSeatReleased::DECLINED);
        }

        return $posto->status;
    }
}
