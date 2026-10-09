<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Notifications\GuestBookingMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Un ospite senza account chiede un posto, a una sessione o a più sessioni dal
 * calendario del mese. Una sessione al giorno: non ci si sdoppia fra due tavoli.
 * Le richieste restano invisibili finché non apre il link dell'unica email: un
 * indirizzo inventato o di un altro non arriva mai al DM.
 */
final class RequestGuestSeat
{
    /** Null se era già dentro, o se quel giorno ha già un tavolo: a chi chiede si risponde comunque allo stesso modo. */
    /** @param  array{name: string, email: string, phone?: ?string, social?: ?string, note?: ?string}  $ospite */
    public function handle(GameSession $session, array $ospite): ?SessionBooking
    {
        if (! $session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        return $this->handleMany(collect([$session]), $ospite)->first();
    }

    /**
     * @param  Collection<int, GameSession>  $sessioni
     * @param  array{name: string, email: string, phone?: ?string, social?: ?string, note?: ?string}  $ospite
     * @return Collection<int, SessionBooking> le richieste nuove o rimandate; quelle saltate non ci sono
     */
    public function handleMany(Collection $sessioni, array $ospite): Collection
    {
        $pulito = fn (string $chiave) => filled($ospite[$chiave] ?? null) ? trim((string) $ospite[$chiave]) : null;
        $email = (string) $pulito('email');

        // Già impegnato quel giorno (una richiesta attiva su un'altra sessione): il giorno è preso.
        $giorniPresi = SessionBooking::query()
            ->whereNull('user_id')
            ->where('guest_email', $email)
            ->whereNotIn('status', [SeatStatus::Unverified->value, SeatStatus::Withdrawn->value])
            ->with('session')
            ->get()
            ->map(fn (SessionBooking $b) => $b->session?->played_at?->toDateString())
            ->filter()
            ->all();

        $posti = DB::transaction(function () use ($sessioni, $pulito, $email, $giorniPresi) {
            $posti = collect();

            // Una al giorno anche se il browser ne manda di più: vince la prima.
            foreach ($sessioni->filter->acceptsBookings()->unique(fn (GameSession $s) => $s->played_at->toDateString()) as $sessione) {
                if (in_array($sessione->played_at->toDateString(), $giorniPresi, true)) {
                    continue;
                }

                $esistente = $sessione->bookings()->whereNull('user_id')->where('guest_email', $email)->latest('id')->first();

                if ($esistente?->status->isActive()) {
                    continue;
                }

                // Una richiesta non verificata si rimanda con dati nuovi, invece di accumulare righe.
                $posto = $esistente?->status === SeatStatus::Unverified ? $esistente : new SessionBooking;

                $posto->forceFill([
                    'game_session_id' => $sessione->getKey(),
                    'guest_name' => $pulito('name'),
                    'guest_email' => $email,
                    'guest_phone' => $pulito('phone'),
                    'guest_social' => $pulito('social'),
                    // «Sai già che personaggio vuoi giocare?»: una nota per i DM, non il nome di una scheda.
                    'guest_note' => $pulito('note'),
                    'guest_token' => (string) Str::uuid(),
                    'status' => SeatStatus::Unverified,
                    'joined_at' => now(),
                ])->save();

                $posti->push($posto);
            }

            return $posti;
        });

        if ($posti->isNotEmpty()) {
            Notification::route('mail', $email)->notify(new GuestBookingMail(
                $posti->first(),
                GuestBookingMail::VERIFY,
                $posti->map(fn (SessionBooking $p) => GuestBookingMail::sessionLine($p->session))->all(),
            ));
        }

        return $posti;
    }

    /**
     * Il clic sul link dell'email vale per tutte le richieste ancora da verificare
     * con quell'indirizzo: entrano fra quelle che vede il DM, in ordine da adesso.
     *
     * @return Collection<int, SessionBooking> le richieste dello stesso indirizzo, da mostrare all'ospite
     */
    public function verify(SessionBooking $posto): Collection
    {
        $daVerificare = SessionBooking::query()
            ->whereNull('user_id')
            ->where('guest_email', $posto->guest_email)
            ->where('status', SeatStatus::Unverified->value)
            ->with('session')
            ->get()
            ->filter(fn (SessionBooking $b) => $b->session?->acceptsBookings());

        if ($posto->status === SeatStatus::Unverified && ! $posto->session->acceptsBookings()) {
            throw SessionUnavailableException::closed();
        }

        foreach ($daVerificare as $b) {
            $b->forceFill(['status' => SeatStatus::Requested, 'joined_at' => now()])->save();
        }

        return SessionBooking::query()
            ->whereNull('user_id')
            ->where('guest_email', $posto->guest_email)
            ->whereNotIn('status', [SeatStatus::Unverified->value, SeatStatus::Withdrawn->value])
            ->with('session.campaign')
            ->get()
            ->filter(fn (SessionBooking $b) => $b->session?->acceptsBookings())
            ->sortBy(fn (SessionBooking $b) => $b->session->played_at)
            ->values();
    }
}
