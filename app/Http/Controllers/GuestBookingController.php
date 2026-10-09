<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\AnswerReserveQuestion;
use App\Actions\Sessions\AnswerSessionOffer;
use App\Actions\Sessions\RequestGuestSeat;
use App\Actions\Sessions\WithdrawFromSession;
use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\GameSession;
use App\Models\SessionBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Gli ospiti senza account: chiedono un posto dal modulo pubblico e lo
 * gestiscono dalla pagina del loro link. Il token è l'unico accesso, quindi
 * la pagina mostra solo i dati che l'ospite ha scritto lui.
 */
class GuestBookingController extends Controller
{
    /** Il limitatore del modulo pubblico, registrato in AppServiceProvider. */
    public const LIMITATORE = 'richieste-ospiti';

    public function create(Request $request, GameSession $session): View|RedirectResponse
    {
        // Chi ha un account chiede il posto dalla pagina della sessione, col suo personaggio.
        if ($request->user()) {
            return redirect()->route('sessions.show', $session);
        }

        return view('guest-bookings.create', ['session' => $session->load('campaign')]);
    }

    /** Il calendario del mese per chi non ha un account: le sessioni da giocare, una al giorno al massimo. */
    public function calendar(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('sessions.index');
        }

        // Un mese scritto storto nell'indirizzo ricade su quello corrente, niente 404.
        $mese = rescue(
            fn () => Carbon::createFromFormat('Y-m', (string) $request->query('mese'))->startOfMonth(),
            fn () => now()->startOfMonth(),
            report: false,
        );

        $sessioni = GameSession::query()
            ->whereBetween('played_at', [$mese->copy()->max(now()), $mese->copy()->endOfMonth()])
            ->with('campaign')
            ->orderBy('played_at')
            ->get();

        return view('guest-bookings.calendar', [
            'mese' => $mese,
            'perGiorno' => $sessioni->groupBy(fn (GameSession $s) => $s->played_at->toDateString()),
            // Nel passato non c'è niente da chiedere.
            'mesePrima' => $mese->copy()->subMonth()->endOfMonth()->isPast() ? null : $mese->copy()->subMonth(),
            'meseDopo' => $mese->copy()->addMonth(),
        ]);
    }

    public function storeCalendar(Request $request): RedirectResponse
    {
        if (filled($request->input('sito_web'))) {
            return redirect()->route('guest-bookings.calendar')->with('inviata', true);
        }

        $dati = $this->validaOspite($request, [
            'sessioni' => ['required', 'array', 'max:31'],
            'sessioni.*' => ['integer'],
        ], [
            'sessioni.required' => 'Scegli almeno una sessione.',
        ]);

        $sessioni = GameSession::whereIn('id', $dati['sessioni'])->orderBy('played_at')->get();

        // Due tavoli lo stesso giorno non si possono giocare: lo si dice invece di sceglierne uno a caso.
        if ($sessioni->countBy(fn (GameSession $s) => $s->played_at->toDateString())->max() > 1) {
            return back()->withInput()->with('error', 'Puoi chiedere una sola sessione al giorno: nello stesso giorno ne hai scelte due.');
        }

        app(RequestGuestSeat::class)->handleMany($sessioni, $dati);

        return redirect()->route('guest-bookings.calendar', ['mese' => $request->query('mese')])->with('inviata', true);
    }

    public function store(Request $request, GameSession $session): RedirectResponse
    {
        // Il campo nascosto lo riempiono solo i bot: si risponde come se fosse andata bene.
        if (filled($request->input('sito_web'))) {
            return redirect()->route('guest-bookings.create', $session)->with('inviata', true);
        }

        $dati = $this->validaOspite($request);

        try {
            app(RequestGuestSeat::class)->handle($session, $dati);
        } catch (SessionUnavailableException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('guest-bookings.create', $session)->with('inviata', true);
    }

    public function verify(string $token): RedirectResponse
    {
        $posto = $this->posto($token);

        try {
            app(RequestGuestSeat::class)->verify($posto);
        } catch (SessionUnavailableException $e) {
            return redirect()->route('guest-bookings.show', $token)->with('error', $e->getMessage());
        }

        return redirect()->route('guest-bookings.show', $token)
            ->with('status', 'Email confermata: la richiesta è arrivata al dungeon master.');
    }

    public function show(string $token): View
    {
        $posto = $this->posto($token)->load('session.campaign');

        return view('guest-bookings.show', [
            'posto' => $posto,
            // Le altre sessioni chieste con la stessa email: chi apre il link è il padrone di quell'indirizzo.
            'altre' => SessionBooking::query()
                ->whereNull('user_id')
                ->where('guest_email', $posto->guest_email)
                ->whereKeyNot($posto->getKey())
                ->whereNotIn('status', [SeatStatus::Unverified->value, SeatStatus::Withdrawn->value])
                ->whereHas('session', fn ($q) => $q->where('played_at', '>', now()))
                ->with('session.campaign')
                ->get()
                ->sortBy(fn (SessionBooking $b) => $b->session->played_at)
                ->values(),
        ]);
    }

    public function answerOffer(Request $request, string $token): RedirectResponse
    {
        try {
            $stato = app(AnswerSessionOffer::class)->handle($this->posto($token), $request->input('risposta') === 'si');
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $stato === SeatStatus::Confirmed
            ? 'Posto confermato: ci vediamo alla sessione.'
            : 'Hai rinunciato al posto.');
    }

    public function answerReserve(Request $request, string $token): RedirectResponse
    {
        try {
            $stato = app(AnswerReserveQuestion::class)->handle($this->posto($token), $request->input('risposta') === 'si');
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $stato === SeatStatus::Reserve
            ? 'Sei fra le riserve: se si libera un posto, potresti essere chiamato.'
            : 'Richiesta ritirata.');
    }

    public function withdraw(string $token): RedirectResponse
    {
        try {
            app(WithdrawFromSession::class)->handle($this->posto($token));
        } catch (SessionUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Richiesta ritirata.');
    }

    /** I dati dell'ospite, uguali per il modulo di una sessione e per quello del calendario. */
    private function validaOspite(Request $request, array $altreRegole = [], array $altriMessaggi = []): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'social' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:255'],
            'privacy' => ['accepted'],
            ...$altreRegole,
        ], [
            'name.required' => 'Scrivi come ti chiami.',
            'email.required' => 'Serve un\x27email: ti arriverà il link per confermare.',
            'privacy.accepted' => 'Serve il tuo consenso per usare questi dati.',
            ...$altriMessaggi,
        ]);
    }

    private function posto(string $token): SessionBooking
    {
        return SessionBooking::where('guest_token', $token)->whereNull('user_id')->firstOrFail();
    }
}
