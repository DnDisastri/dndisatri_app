<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\AddGuest;
use App\Actions\Sessions\AnswerReserveQuestion;
use App\Actions\Sessions\AnswerSessionOffer;
use App\Actions\Sessions\AskReserves;
use App\Actions\Sessions\LinkGuest;
use App\Actions\Sessions\OfferSessionSeat;
use App\Actions\Sessions\RequestSessionSeat;
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
use Illuminate\View\View;
use InvalidArgumentException;

/** Chiedere un posto, rispondere all'offerta, ritirarsi; e i gesti del DM sui posti. */
class SessionBookingController extends Controller
{
    /** Le proprie prenotazioni in un posto solo, divise per quello che c'è da fare. */
    public function mine(Request $request): View
    {
        $posti = $request->user()->sessionBookings()
            ->whereNotIn('status', [SeatStatus::Withdrawn->value, SeatStatus::Unverified->value])
            ->whereHas('session', fn ($q) => $q->where('played_at', '>', now()))
            ->with(['session.campaign', 'character'])
            ->get()
            ->sortBy(fn (SessionBooking $p) => $p->session->played_at)
            ->values();

        return view('sessions.mine', [
            'daConfermare' => $posti->where('status', SeatStatus::Offered),
            'domandeRiserva' => $posti->filter->awaitsReserveAnswer(),
            'confermate' => $posti->where('status', SeatStatus::Confirmed),
            'inviate' => $posti->filter(fn (SessionBooking $p) => $p->status === SeatStatus::Requested && ! $p->awaitsReserveAnswer()),
            'riserve' => $posti->where('status', SeatStatus::Reserve),
            'scadute' => $posti->where('status', SeatStatus::Expired),
        ]);
    }

    /** Dal calendario: un posto a più sessioni insieme, con lo stesso eroe, una al giorno. */
    public function bookMany(Request $request): RedirectResponse
    {
        $utente = $request->user();

        $dati = $request->validate([
            'sessioni' => ['required', 'array', 'max:31'],
            'sessioni.*' => ['integer'],
            'character_id' => ['required', 'integer', Rule::exists('characters', 'id')],
        ], [
            'sessioni.required' => 'Scegli almeno una sessione.',
            'character_id.required' => 'Scegli con quale eroe vieni.',
        ]);

        $sessioni = GameSession::whereIn('id', $dati['sessioni'])->with('campaign')->orderBy('played_at')->get();

        if ($sessioni->countBy(fn (GameSession $s) => $s->played_at->toDateString())->max() > 1) {
            return back()->withInput()->with('error', 'Puoi chiedere una sola sessione al giorno: nello stesso giorno ne hai scelte due.');
        }

        $eroe = Character::findOrFail($dati['character_id']);

        if ($eroe->user_id !== $utente->getKey() || ! $eroe->isAlive()) {
            return back()->withInput()->with('error', SessionUnavailableException::wrongCharacter()->getMessage());
        }
        $fatte = 0;
        $saltate = [];

        foreach ($sessioni as $sessione) {
            if ($utente->cannot('book', $sessione)) {
                $saltate[] = $sessione->campaign?->title.' del '.$sessione->played_at->translatedFormat('j F');

                continue;
            }

            try {
                app(RequestSessionSeat::class)->handle($sessione, $utente, $eroe);
                $fatte++;
            } catch (SessionUnavailableException) {
                $saltate[] = $sessione->campaign?->title.' del '.$sessione->played_at->translatedFormat('j F');
            }
        }

        $messaggio = match (true) {
            $fatte === 0 => 'Nessuna richiesta inviata.',
            $fatte === 1 => 'Richiesta inviata per 1 sessione.',
            default => "Richiesta inviata per {$fatte} sessioni.",
        };

        if ($fatte > 0) {
            $messaggio .= ' Se c\'è posto per te, ti arriverà un\'email per confermarlo. Le trovi tutte in «Le mie prenotazioni».';
        }

        if ($saltate !== []) {
            $messaggio .= ' Non inviate (già chieste, o un altro tavolo lo stesso giorno): '.implode(', ', $saltate).'.';
        }

        return back()->with($fatte > 0 ? 'status' : 'error', $messaggio);
    }

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
            app(RequestSessionSeat::class)->handle($session, $utente, Character::findOrFail($dati['character_id']));
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $giaDentro
            ? 'Personaggio cambiato.'
            : 'Richiesta inviata: hai chiesto un posto per questa sessione. Se c\'è posto per te, ti arriverà un\'email per confermarlo.');
    }

    public function withdraw(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('withdraw', $session);

        try {
            app(WithdrawFromSession::class)->handle($session->bookingOf($request->user()));
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Fatto: hai lasciato la sessione.');
    }

    /** `risposta`: «si» conferma il posto offerto, qualsiasi altra cosa ci rinuncia. */
    public function answerOffer(Request $request, GameSession $session): RedirectResponse
    {
        $posto = $session->bookingOf($request->user());
        abort_if($posto === null, 404);

        try {
            $stato = app(AnswerSessionOffer::class)->handle($posto, $request->input('risposta') === 'si');
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $stato === SeatStatus::Confirmed
            ? 'Posto confermato: ci vediamo alla sessione.'
            : 'Hai rinunciato al posto. Il dungeon master lo sa.');
    }

    public function answerReserve(Request $request, GameSession $session): RedirectResponse
    {
        $posto = $session->bookingOf($request->user());
        abort_if($posto === null, 404);

        try {
            $stato = app(AnswerReserveQuestion::class)->handle($posto, $request->input('risposta') === 'si');
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $stato === SeatStatus::Reserve
            ? 'Sei fra le riserve: se si libera un posto, potresti essere chiamato.'
            : 'Richiesta ritirata.');
    }

    public function offer(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('manageSeats', $session);

        $dati = $request->validate([
            'booking_id' => ['required', 'integer'],
        ]);

        try {
            $posto = app(OfferSessionSeat::class)->handle($session, $this->posto($session, $dati['booking_id']));
        } catch (SessionUnavailableException $e) {
            // Fra il caricamento e il clic un posto può essersi riempito.
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $posto->status === SeatStatus::Confirmed
            ? "{$posto->displayName()} ha il posto confermato: non ha un'email, avvisalo tu."
            : "Posto offerto a {$posto->displayName()}: ha ".SessionBooking::OFFER_HOURS.' ore per confermare.');
    }

    public function addGuest(Request $request, GameSession $session): RedirectResponse
    {
        $this->authorize('addGuest', $session);

        $dati = $request->validate([
            'guest_name' => ['required', 'string', 'max:80'],
            'guest_character' => ['nullable', 'string', 'max:80'],
            'guest_note' => ['nullable', 'string', 'max:255'],
            'guest_social' => ['nullable', 'required_without:guest_email', 'string', 'max:80'],
            'guest_email' => ['nullable', 'string', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
        ], [
            'guest_name.required' => 'Scrivi il nome dell\'ospite.',
            'guest_social.required_without' => 'Serve almeno un contatto: Instagram o Telegram, oppure l\'email.',
        ]);

        try {
            $posto = app(AddGuest::class)->handle($session, $request->user(), $dati['guest_name'], [
                'email' => $dati['guest_email'] ?? null,
                'social' => $dati['guest_social'] ?? null,
                'phone' => $dati['guest_phone'] ?? null,
                'character' => $dati['guest_character'] ?? null,
                'note' => $dati['guest_note'] ?? null,
            ]);
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        app(AskReserves::class)->handle($session);

        return back()->with('status', $posto->status === SeatStatus::Reserve
            ? 'Posti esauriti: l\'ospite è fra le riserve.'
            : 'Ospite aggiunto, col posto confermato.');
    }

    public function removeGuest(GameSession $session, int $booking): RedirectResponse
    {
        $this->authorize('manageGuests', $session);

        $posto = $this->posto($session, $booking);
        abort_unless($posto->isGuest(), 404);

        $posto->forceFill(['status' => SeatStatus::Withdrawn, 'decided_at' => now(), 'offer_expires_at' => null])->save();

        return back()->with('status', 'Ospite tolto.');
    }

    /** L'ospite si è registrato: il posto passa al suo account. */
    public function linkGuest(Request $request, GameSession $session, int $booking): RedirectResponse
    {
        $this->authorize('manageGuests', $session);

        // Per nome: è univoco, ed è quello che il DM scrive nel campo con i suggerimenti.
        // L'errore va in cima alla pagina: accanto al campo comparirebbe sotto ogni ospite.
        $giocatore = User::where('name', (string) $request->input('user_name'))->first();

        if ($giocatore === null || $giocatore->isAdmin()) {
            return back()->with('error', 'Nessun giocatore si chiama così: scegli il nome fra i suggerimenti.');
        }

        $posto = $this->posto($session, $booking);
        abort_unless($posto->isGuest(), 404);

        try {
            app(LinkGuest::class)->handle($posto, $giocatore);
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
