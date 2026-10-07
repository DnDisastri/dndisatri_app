<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Un ospite prenotato fuori dall'app: occupa un posto come gli altri, o va in lista d'attesa. */
final class AddGuest
{
    public function handle(GameSession $session, User $dm, string $nome, ?string $personaggio = null, ?string $nota = null): SessionBooking
    {
        return DB::transaction(function () use ($session, $dm, $nome, $personaggio, $nota) {
            $locked = GameSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            $posto = new SessionBooking;
            $posto->forceFill([
                'game_session_id' => $locked->getKey(),
                'guest_name' => trim($nome),
                'guest_character' => filled($personaggio) ? trim($personaggio) : null,
                'guest_note' => filled($nota) ? trim($nota) : null,
                'status' => $locked->isFull() ? SeatStatus::Waiting : ($locked->isConfirmed() ? SeatStatus::Confirmed : SeatStatus::Booked),
                'joined_at' => now(),
                'added_by' => $dm->getKey(),
            ])->save();

            return $posto;
        });
    }
}
