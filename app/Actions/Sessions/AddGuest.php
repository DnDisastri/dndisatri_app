<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use App\Notifications\GuestBookingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Un ospite già d'accordo col DM fuori dall'app, di solito su Instagram o
 * Telegram: entra confermato se c'è posto, altrimenti fra le riserve. Con
 * l'email riceve il suo link, da cui può disdire.
 */
final class AddGuest
{
    /** @param array{email?: ?string, social?: ?string, phone?: ?string, character?: ?string, note?: ?string} $dati */
    public function handle(GameSession $session, User $dm, string $nome, array $dati = []): SessionBooking
    {
        $pulito = fn (string $chiave) => filled($dati[$chiave] ?? null) ? trim((string) $dati[$chiave]) : null;

        $posto = DB::transaction(function () use ($session, $dm, $nome, $pulito) {
            $locked = GameSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->acceptsBookings()) {
                throw SessionUnavailableException::closed();
            }

            $email = $pulito('email');

            $posto = new SessionBooking;
            $posto->forceFill([
                'game_session_id' => $locked->getKey(),
                'guest_name' => trim($nome),
                'guest_character' => $pulito('character'),
                'guest_note' => $pulito('note'),
                'guest_email' => $email,
                'guest_phone' => $pulito('phone'),
                'guest_social' => $pulito('social'),
                'guest_token' => $email ? (string) Str::uuid() : null,
                'status' => $locked->isFull() ? SeatStatus::Reserve : SeatStatus::Confirmed,
                'joined_at' => now(),
                'decided_at' => now(),
                'added_by' => $dm->getKey(),
            ])->save();

            return $posto;
        });

        if ($posto->guest_email) {
            Notification::route('mail', $posto->guest_email)->notify(new GuestBookingMail($posto, GuestBookingMail::ADDED));
        }

        return $posto;
    }
}
