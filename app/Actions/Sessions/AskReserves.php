<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Notifications\GuestBookingMail;
use App\Notifications\SessionReserveAsked;
use Illuminate\Support\Facades\Notification;

/**
 * A posti tutti confermati, chi ha chiesto e non è stato scelto riceve la
 * domanda «resti fra le riserve?». Una volta sola per persona.
 */
final class AskReserves
{
    public function handle(GameSession $session): int
    {
        if (! $session->isFullyConfirmed()) {
            return 0;
        }

        $daChiedere = $session->bookings()
            ->where('status', SeatStatus::Requested->value)
            ->whereNull('reserve_asked_at')
            ->with('user')
            ->get();

        foreach ($daChiedere as $posto) {
            /** @var SessionBooking $posto */
            $posto->forceFill(['reserve_asked_at' => now()])->save();

            if ($posto->user !== null) {
                $posto->user->notify(new SessionReserveAsked($session));
            } elseif (filled($posto->guest_email)) {
                Notification::route('mail', $posto->guest_email)->notify(new GuestBookingMail($posto, GuestBookingMail::RESERVE));
            }
        }

        return $daChiedere->count();
    }
}
