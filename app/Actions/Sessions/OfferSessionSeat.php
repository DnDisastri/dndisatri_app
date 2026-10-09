<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Notifications\GuestBookingMail;
use App\Notifications\SessionSeatOffered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Il DM sceglie chi far giocare, anche fuori dall'ordine di arrivo. Il posto
 * resta da parte fino alla scadenza: 24 ore, o l'inizio della sessione se
 * viene prima. Un ospite senza email lo sente il DM fuori dall'app, quindi
 * entra subito confermato. La sessione resta bloccata, o due offerte
 * sull'ultimo posto passerebbero entrambe.
 */
final class OfferSessionSeat
{
    public function __construct(private readonly AskReserves $askReserves) {}

    public function handle(GameSession $session, SessionBooking $booking): SessionBooking
    {
        $posto = DB::transaction(function () use ($session, $booking) {
            $locked = GameSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $posto = SessionBooking::whereKey($booking->getKey())->where('game_session_id', $locked->getKey())->firstOrFail();

            if (! $locked->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            if (! $posto->status->canBeOffered()) {
                throw SessionUnavailableException::cannotOffer();
            }

            if ($locked->isFull()) {
                throw SessionUnavailableException::full();
            }

            $diretto = $posto->answersOutsideApp();

            $posto->forceFill([
                'status' => $diretto ? SeatStatus::Confirmed : SeatStatus::Offered,
                'decided_at' => now(),
                'offer_expires_at' => $diretto ? null : now()->addHours(SessionBooking::OFFER_HOURS)->min($locked->played_at),
            ])->save();

            return $posto;
        });

        if ($posto->status === SeatStatus::Confirmed) {
            $this->askReserves->handle($posto->session);
        } elseif ($posto->user !== null) {
            $posto->user->notify(new SessionSeatOffered($posto));
        } else {
            Notification::route('mail', $posto->guest_email)->notify(new GuestBookingMail($posto, GuestBookingMail::OFFERED));
        }

        return $posto;
    }
}
