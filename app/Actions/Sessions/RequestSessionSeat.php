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
 * Chiedere un posto con uno dei propri personaggi, anche a sessione piena:
 * il posto non è ancora di nessuno, sceglie il DM.
 */
final class RequestSessionSeat
{
    public function handle(GameSession $session, User $user, Character $character): SeatStatus
    {
        if ($character->user_id !== $user->getKey() || ! $character->isAlive()) {
            throw SessionUnavailableException::wrongCharacter();
        }

        if (! $session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        return DB::transaction(function () use ($session, $user, $character) {
            $attuale = $session->bookingOf($user);

            // Una sessione al giorno: chi ha già un tavolo quel giorno non ne chiede un secondo.
            if (! $attuale?->status->isActive() && $this->giornoPreso($session, $user)) {
                throw SessionUnavailableException::sameDay();
            }

            // Già dentro: si cambia solo il personaggio, lo stato resta com'è.
            if ($attuale?->status->isActive()) {
                $attuale->forceFill(['character_id' => $character->getKey()])->save();

                return $attuale->status;
            }

            $riga = [
                'character_id' => $character->getKey(),
                'status' => SeatStatus::Requested->value,
                'joined_at' => now(),
                'decided_at' => null,
                'offer_expires_at' => null,
                'reserve_asked_at' => null,
            ];

            // Chi si era ritirato riusa la sua riga: la chiave unica non ne ammette due.
            $attuale === null
                ? $session->players()->attach($user, $riga)
                : $attuale->forceFill($riga)->save();

            return SeatStatus::Requested;
        });
    }

    private function giornoPreso(GameSession $session, User $user): bool
    {
        return $user->sessionBookings()
            ->where('game_session_id', '!=', $session->getKey())
            ->whereNotIn('status', [SeatStatus::Withdrawn->value, SeatStatus::Unverified->value])
            ->whereHas('session', fn ($q) => $q->whereDate('played_at', $session->played_at->toDateString()))
            ->exists();
    }
}
