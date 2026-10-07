<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Prenotarsi a una sessione con uno dei propri personaggi.
 *
 * A posti esauriti non si rifiuta: si entra in lista d'attesa. La riga della
 * sessione resta bloccata per tutta la transazione, o due prenotazioni
 * sull'ultimo posto passerebbero entrambe.
 */
final class BookSessionSeat
{
    public function handle(GameSession $session, User $user, Character $character): SeatStatus
    {
        if ($character->user_id !== $user->getKey() || ! $character->isAlive()) {
            throw SessionUnavailableException::wrongCharacter();
        }

        return DB::transaction(function () use ($session, $user, $character) {
            $locked = GameSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            $attuale = $locked->seatOf($user);

            // Già dentro: si cambia solo il personaggio, il posto resta com'è.
            if ($attuale !== null && $attuale->isActive()) {
                $locked->players()->updateExistingPivot($user, ['character_id' => $character->getKey()]);

                return $attuale;
            }

            $nuovo = $locked->isFull() ? SeatStatus::Waiting : SeatStatus::Booked;

            $riga = [
                'character_id' => $character->getKey(),
                'status' => $nuovo->value,
                'joined_at' => now(),
                'decided_at' => null,
            ];

            // Chi si era ritirato riusa la sua riga: la chiave unica non ne ammette due.
            $attuale === null
                ? $locked->players()->attach($user, $riga)
                : $locked->players()->updateExistingPivot($user, $riga);

            return $nuovo;
        });
    }
}
