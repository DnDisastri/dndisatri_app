<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Notifications\SessionSeatConfirmed;
use Illuminate\Support\Facades\DB;

/**
 * «La sessione si fa»: tutti i prenotati, ospiti compresi, diventano confermati insieme.
 * Il minimo non si controlla, la decisione è del DM. La lista d'attesa resta com'è.
 */
final class ConfirmSessionPlayers
{
    public function handle(GameSession $session): GameSession
    {
        if (! $session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        return DB::transaction(function () use ($session) {
            $prenotati = $session->bookings()->where('status', SeatStatus::Booked->value)->with('user')->get();

            $session->forceFill(['players_confirmed_at' => now()])->save();

            foreach ($prenotati as $posto) {
                $posto->forceFill(['status' => SeatStatus::Confirmed, 'decided_at' => now()])->save();

                // L'ospite non ha un account: lo avvisa il DM, fuori dall'app.
                $posto->user?->notify(new SessionSeatConfirmed($session));
            }

            return $session->fresh();
        });
    }
}
