<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Models\SessionBooking;
use App\Notifications\SessionSeatReleased;

/** Le offerte non confermate in tempo tornano libere, e il DM lo sa. Gira dallo scheduler. */
final class ExpireSessionOffers
{
    public function __construct(private readonly NotifySeatReleased $notifyReleased) {}

    public function handle(): int
    {
        $scadute = SessionBooking::query()
            ->where('status', SeatStatus::Offered->value)
            ->where('offer_expires_at', '<=', now())
            ->with(['session.campaign.dm', 'user'])
            ->get();

        foreach ($scadute as $posto) {
            $posto->forceFill(['status' => SeatStatus::Expired, 'offer_expires_at' => null])->save();

            // A sessione cominciata il posto non serve più a nessuno: niente avviso.
            if ($posto->session->acceptsBookings()) {
                $this->notifyReleased->handle($posto, SessionSeatReleased::EXPIRED);
            }
        }

        return $scadute->count();
    }
}
