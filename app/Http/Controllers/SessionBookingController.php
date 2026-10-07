<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\AddGuest;
use App\Actions\Sessions\BookSessionSeat;
use App\Actions\Sessions\ConfirmSessionPlayers;
use App\Actions\Sessions\LinkGuest;
use App\Actions\Sessions\PromoteFromWaitingList;
use App\Actions\Sessions\WithdrawFromSession;
use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/** Prenotarsi a una sessione, ritirarsi, e i due gesti del DM sui posti. */
class SessionBookingController extends Controller
{
    public function book(Request $request, GameSession $session): RedirectResponse
    {
        $utente = $request->user();
        $giaDentro = $session->hasParticipant($utente);

        // Chi è già dentro può solo cambiare personaggio.
        $this->authorize($giaDentro ? 'withdraw' : 'book', $session);

        $dati = $request->validate([
            'character_id' => ['required', 'integer', Rule::exists('characters', 'id')],
        ], [
            'character_id.required' => 'Scegli con quale personaggio vieni.',
        ]);

        try {
            $stato = app(BookSessionSeat::class)->handle($session, $utente, Character::findOrFail($dati['character_id']));
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', match (true) {
            $giaDentro => 'Personaggio cambiato.',
            $stato === SeatStatus::Waiting => 'I posti erano esauriti: sei in lista d\'attesa. Se qualcuno si ritira, il DM ti chiama.',
            default => 'Prenotato. Il posto è tuo quando il DM conferma la sessione.',
        });
    }

    public function withdraw(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('withdraw', $session);

        app(WithdrawFromSession::class)->handle($session, $request->user());

        return back()->with('status', 'Ti sei tirato indietro.');
    }

    public function confirm(GameSession $session): RedirectResponse
    {
        $this->authorize('confirmPlayers', $session);

        app(ConfirmSessionPlayers::class)->handle($session);

        return back()->with('status', 'Sessione confermata: i prenotati hanno ricevuto la notifica.');
    }

    public function promote(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('promote', $session);

        $dati = $request->validate([
            'booking_id' => ['required', 'integer'],
        ]);

        try {
            app(PromoteFromWaitingList::class)->handle($session, $this->posto($session, $dati['booking_id']));
        } catch (SessionUnavailableException $e) {
            // Fra il caricamento e il clic un posto può essersi riempito.
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Chiamato dalla lista d\'attesa.');
    }

    public function addGuest(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('addGuest', $session);

        $dati = $request->validate([
            'guest_name' => ['required', 'string', 'max:80'],
            'guest_character' => ['nullable', 'string', 'max:80'],
            'guest_note' => ['nullable', 'string', 'max:255'],
        ], [
            'guest_name.required' => 'Scrivi il nome dell\'ospite.',
        ]);

        try {
            $posto = app(AddGuest::class)->handle(
                $session, $request->user(), $dati['guest_name'], $dati['guest_character'] ?? null, $dati['guest_note'] ?? null,
            );
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $posto->status === SeatStatus::Waiting
            ? 'Posti esauriti: l\'ospite è in lista d\'attesa.'
            : 'Ospite aggiunto.');
    }

    public function removeGuest(GameSession $session, int $booking): RedirectResponse
    {
        $this->authorize('manageGuests', $session);

        $posto = $this->posto($session, $booking);
        abort_unless($posto->isGuest(), 404);

        $posto->forceFill(['status' => SeatStatus::Withdrawn, 'decided_at' => now()])->save();

        return back()->with('status', 'Ospite tolto.');
    }

    /** L'ospite si è registrato: il posto passa al suo account. */
    public function linkGuest(Request $request, GameSession $session, int $booking): RedirectResponse
    {
        $this->authorize('manageGuests', $session);

        $dati = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $posto = $this->posto($session, $booking);
        abort_unless($posto->isGuest(), 404);

        try {
            app(LinkGuest::class)->handle($posto, User::findOrFail($dati['user_id']));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Posto collegato all\'account.');
    }

    /** Il posto si cerca fra quelli della sessione: un id arrivato dal browser non basta. */
    private function posto(GameSession $session, int $id): SessionBooking
    {
        return $session->bookings()->whereKey($id)->firstOrFail();
    }
}
