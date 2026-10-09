<?php

declare(strict_types=1);

use App\Actions\Sessions\AddGuest;
use App\Actions\Sessions\LinkGuest;
use App\Actions\Sessions\OfferSessionSeat;
use App\Actions\Sessions\RequestSessionSeat;
use App\Enums\SeatStatus;
use App\Livewire\CombatTracker;
use App\Models\Character;
use App\Models\Encounter;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use App\Notifications\GuestBookingMail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->dm = User::factory()->dm()->create();
    $this->sessione = GameSession::factory()->upcoming()->seats(2)->create();
});

function ospite(GameSession $sessione, User $dm, string $nome, array $dati = ['social' => '@ospite']): SessionBooking
{
    return app(AddGuest::class)->handle($sessione->fresh(), $dm, $nome, $dati);
}

function mailAOspite(string $email, string $tipo): void
{
    Notification::assertSentTo(
        new AnonymousNotifiable,
        GuestBookingMail::class,
        fn (GuestBookingMail $mail, array $canali, AnonymousNotifiable $a) => $a->routes['mail'] === $email
            && (fn () => $this->tipo)->call($mail) === $tipo,
    );
}

function richiestaOspite(array $dati = []): array
{
    return array_merge([
        'name' => 'Lia',
        'email' => 'lia@example.com',
        'phone' => '333 1234567',
        'privacy' => '1',
    ], $dati);
}

describe('gli ospiti aggiunti dal DM', function () {
    it('entrano confermati col nome, e i contatti li vede solo il DM', function () {
        $this->actingAs($this->dm)->post(route('sessions.guests.store', $this->sessione), [
            'guest_name' => 'Marco da Instagram',
            'guest_social' => '@marco_ig',
            'guest_character' => 'Pregenerato guerriero',
        ])->assertRedirect()->assertSessionHas('status');

        expect($this->sessione->fresh()->confirmedCount())->toBe(1);

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $this->sessione))
            ->assertOk()
            ->assertSee('Marco da Instagram')
            ->assertSee('ospite')
            ->assertSee('Pregenerato guerriero')
            ->assertDontSee('@marco_ig');

        $this->actingAs($this->dm)->get(route('sessions.show', $this->sessione))->assertSee('@marco_ig');
    });

    it('serve almeno un contatto: Instagram o Telegram, oppure l\'email', function () {
        $this->actingAs($this->dm)
            ->post(route('sessions.guests.store', $this->sessione), ['guest_name' => 'Senza contatti'])
            ->assertSessionHasErrors('guest_social');
    });

    it('con l\'email ricevono il link per disdire', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco', ['email' => 'marco@example.com']);

        expect($posto->guest_token)->not->toBeNull();
        mailAOspite('marco@example.com', GuestBookingMail::ADDED);
    });

    it('un giocatore non aggiunge ospiti', function () {
        $this->actingAs(User::factory()->player()->create())
            ->post(route('sessions.guests.store', $this->sessione), ['guest_name' => 'Intruso', 'guest_social' => '@x'])
            ->assertForbidden();
    });

    it('a posti pieni vanno fra le riserve', function () {
        ospite($this->sessione, $this->dm, 'Primo');
        ospite($this->sessione, $this->dm, 'Secondo');

        expect(ospite($this->sessione, $this->dm, 'Terzo')->status)->toBe(SeatStatus::Reserve);
    });

    it('senza email, quando il DM gli offre il posto entra subito confermato: lo avvisa il DM', function () {
        ospite($this->sessione, $this->dm, 'Primo');
        ospite($this->sessione, $this->dm, 'Secondo');
        $riserva = ospite($this->sessione, $this->dm, 'Terzo');
        $this->sessione->bookings()->where('guest_name', 'Primo')->first()->forceFill(['status' => SeatStatus::Withdrawn])->save();

        expect(app(OfferSessionSeat::class)->handle($this->sessione->fresh(), $riserva)->status)->toBe(SeatStatus::Confirmed);
    });

    it('si tolgono e liberano il posto', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco');

        $this->actingAs($this->dm)->post(route('sessions.guests.remove', [$this->sessione, $posto->id]))->assertRedirect();

        expect($posto->fresh()->status)->toBe(SeatStatus::Withdrawn)
            ->and($this->sessione->fresh()->participantCount())->toBe(0);
    });

    it('quando si registra, il posto passa al suo account', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco', ['email' => 'marco@example.com', 'social' => '@marco']);
        $marco = User::factory()->player()->create(['name' => 'Marco Registrato']);

        $this->actingAs($this->dm)
            ->post(route('sessions.guests.link', [$this->sessione, $posto->id]), ['user_name' => 'Marco Registrato'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $posto = $posto->fresh();
        expect($this->sessione->fresh()->seatOf($marco))->toBe(SeatStatus::Confirmed)
            ->and($posto->isGuest())->toBeFalse()
            ->and($posto->guest_email)->toBeNull()
            ->and($posto->guest_token)->toBeNull();
    });

    it('un nome che non esiste, o di un admin, non collega niente', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco');
        User::factory()->admin()->create(['name' => 'Un Admin']);

        foreach (['Nessuno Così', 'Un Admin'] as $nome) {
            $this->actingAs($this->dm)
                ->post(route('sessions.guests.link', [$this->sessione, $posto->id]), ['user_name' => $nome])
                ->assertSessionHas('error');
        }

        expect($posto->fresh()->isGuest())->toBeTrue();
    });

    it('se si è registrato con la stessa email, il collegamento si propone da solo', function () {
        ospite($this->sessione, $this->dm, 'Marco', ['email' => 'marco@example.com']);
        User::factory()->player()->create(['name' => 'Marco Registrato', 'email' => 'marco@example.com']);
        User::factory()->player()->create(['name' => 'Altro Marco', 'email' => 'Marco@example.com']);

        $this->actingAs($this->dm)->get(route('sessions.show', $this->sessione))
            ->assertSee('Si è registrato come')
            ->assertSee('name="user_name" value="Marco Registrato"', false)
            ->assertDontSee('name="user_name" value="Altro Marco"', false);
    });

    it('non si collega a chi ha già chiesto un posto', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco');
        $pg = Character::factory()->for(User::factory()->player())->create();
        app(RequestSessionSeat::class)->handle($this->sessione->fresh(), $pg->user, $pg);

        expect(fn () => app(LinkGuest::class)->handle($posto, $pg->user))->toThrow(InvalidArgumentException::class);
    });

    it('presente a sessione finita, e collegato dopo, la presenza passa a lui', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco');
        $this->sessione->forceFill(['played_at' => now()->subHour()])->save();

        $this->actingAs($this->dm)->post(route('sessions.attendance', $this->sessione), ['ospiti' => [$posto->id]])->assertRedirect();
        expect($posto->fresh()->guest_attended)->toBeTrue();

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $this->sessione))
            ->assertSee('Chi c\'era', false)
            ->assertSee('Marco');

        $marco = User::factory()->player()->create();
        app(LinkGuest::class)->handle($posto->fresh(), $marco);

        expect($this->sessione->fresh()->attended($marco))->toBeTrue();
    });

    it('nel combattimento l\'ospite diventa il suo eroe quando viene collegato all\'account', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco', ['social' => '@marco', 'character' => 'Pregenerato']);
        $scontro = Encounter::factory()->create([
            'campaign_id' => $this->sessione->campaign_id,
            'game_session_id' => $this->sessione->id,
        ]);

        $tracker = Livewire::actingAs($this->dm)->test(CombatTracker::class, ['encounter' => $scontro])->call('aggiungiEroi');
        $id = collect($tracker->get('combattenti'))->firstWhere('tipo', 'ospite')['id'];
        $tracker->set('combattenti.0.iniziativa', 17);

        $marco = User::factory()->player()->create();
        $eroe = Character::factory()->for($marco)->create(['name' => 'Brunilde Vera']);
        app(LinkGuest::class)->handle($posto->fresh(), $marco);

        expect($posto->fresh()->character_id)->toBe($eroe->id);

        // Riaprendo il combattimento, la riga dell'ospite è diventata la scheda, con la stessa iniziativa.
        $riga = collect(Livewire::actingAs($this->dm)->test(CombatTracker::class, ['encounter' => $scontro->fresh()])->get('combattenti'))
            ->firstWhere('id', $id);

        expect($riga['tipo'])->toBe('pg')
            ->and($riga['characterId'])->toBe($eroe->id)
            ->and($riga['nome'])->toBe('Brunilde Vera')
            ->and($riga['iniziativa'])->toBe(17)
            ->and(collect($scontro->fresh()->combatants)->firstWhere('id', $id)['tipo'])->toBe('pg');
    });

    it('un collegato con più eroi resta ospite finché non sceglie il personaggio', function () {
        $posto = ospite($this->sessione, $this->dm, 'Marco');
        $marco = User::factory()->player()->create(['name' => 'Marco Indeciso']);
        Character::factory()->for($marco)->count(2)->create();
        app(LinkGuest::class)->handle($posto->fresh(), $marco);

        $scontro = Encounter::factory()->create([
            'campaign_id' => $this->sessione->campaign_id,
            'game_session_id' => $this->sessione->id,
        ]);

        $righe = Livewire::actingAs($this->dm)->test(CombatTracker::class, ['encounter' => $scontro])->call('aggiungiEroi')->get('combattenti');

        expect(collect($righe)->firstWhere('nome', 'Marco Indeciso')['tipo'])->toBe('ospite');
    });

    it('il combattimento mette in fila anche gli ospiti, con PF e CA a mano', function () {
        ospite($this->sessione, $this->dm, 'Marco', ['social' => '@marco', 'character' => 'Brunilde']);
        $scontro = Encounter::factory()->create([
            'campaign_id' => $this->sessione->campaign_id,
            'game_session_id' => $this->sessione->id,
        ]);

        $tracker = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $scontro])
            ->call('aggiungiEroi')
            ->assertSee('Brunilde')
            ->assertSee('ospite');

        $indice = collect($tracker->get('combattenti'))->search(fn ($c) => $c['tipo'] === 'ospite');
        $tracker->set("combattenti.{$indice}.hpMax", 20)->set("combattenti.{$indice}.ac", 15);

        $combattente = collect($scontro->fresh()->combatants)->firstWhere('tipo', 'ospite');
        expect($combattente['hpMax'])->toBe(20)->and($combattente['hp'])->toBe(20)->and($combattente['ac'])->toBe(15);
    });
});

describe('il modulo pubblico', function () {
    beforeEach(fn () => RateLimiter::clear('ip:127.0.0.1'));

    it('si apre senza account; chi è dentro va alla pagina della sessione', function () {
        $this->get(route('guest-bookings.create', $this->sessione))->assertOk()->assertSee('Chiedo un posto');

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guest-bookings.create', $this->sessione))
            ->assertRedirect(route('sessions.show', $this->sessione));
    });

    it('la richiesta resta invisibile finché non si clicca il link dell\'email', function () {
        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite())
            ->assertRedirect()
            ->assertSessionHas('inviata');

        $posto = SessionBooking::where('guest_email', 'lia@example.com')->firstOrFail();
        expect($posto->status)->toBe(SeatStatus::Unverified)
            ->and($posto->guest_phone)->toBe('333 1234567');
        mailAOspite('lia@example.com', GuestBookingMail::VERIFY);

        $this->actingAs($this->dm)->get(route('sessions.show', $this->sessione))->assertDontSee('lia@example.com');
        auth()->logout();

        $this->get(route('guest-bookings.verify', $posto->guest_token))->assertRedirect(route('guest-bookings.show', $posto->guest_token));

        expect($posto->fresh()->status)->toBe(SeatStatus::Requested);
        $this->actingAs($this->dm)->get(route('sessions.show', $this->sessione))->assertSee('lia@example.com');
    });

    it('l\'email è obbligatoria, il telefono no', function () {
        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite(['email' => '']))
            ->assertSessionHasErrors('email');

        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite(['phone' => '']))
            ->assertSessionHasNoErrors();
    });

    it('il campo nascosto ferma i bot senza dirglielo', function () {
        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite(['sito_web' => 'http://spam.example']))
            ->assertRedirect()
            ->assertSessionHas('inviata');

        expect(SessionBooking::count())->toBe(0);
        Notification::assertNothingSent();
    });

    it('troppe richieste dallo stesso indirizzo vengono fermate', function () {
        foreach (range(1, 5) as $i) {
            $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite(['email' => "lia{$i}@example.com"]));
        }

        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite(['email' => 'lia6@example.com']))
            ->assertStatus(429);
    });

    it('rimandare con la stessa email non crea un doppione, e non dice se c\'era già', function () {
        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite());
        $posto = SessionBooking::firstOrFail();
        $this->get(route('guest-bookings.verify', $posto->guest_token));

        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite())->assertSessionHas('inviata');

        expect(SessionBooking::count())->toBe(1);
    });

    it('dal link l\'ospite scelto conferma, e poi può disdire', function () {
        $this->post(route('guest-bookings.store', $this->sessione), richiestaOspite());
        $posto = SessionBooking::firstOrFail();
        $this->get(route('guest-bookings.verify', $posto->guest_token));

        app(OfferSessionSeat::class)->handle($this->sessione->fresh(), $posto->fresh());
        mailAOspite('lia@example.com', GuestBookingMail::OFFERED);

        $this->get(route('guest-bookings.show', $posto->guest_token))->assertOk()->assertSee('Confermo il posto');
        $this->post(route('guest-bookings.answer-offer', $posto->guest_token), ['risposta' => 'si'])->assertRedirect();
        expect($posto->fresh()->status)->toBe(SeatStatus::Confirmed);

        $this->post(route('guest-bookings.withdraw', $posto->guest_token))->assertRedirect();
        expect($posto->fresh()->status)->toBe(SeatStatus::Withdrawn);
    });

    it('un token inventato non apre niente', function () {
        $this->get(route('guest-bookings.show', '00000000-0000-4000-8000-000000000000'))->assertNotFound();
    });
});

describe('il calendario del mese per gli ospiti', function () {
    beforeEach(function () {
        RateLimiter::clear('ip:127.0.0.1');

        // Il mese prossimo: è sempre tutto da giocare, qualunque sia il giorno di oggi.
        $this->mese = now()->addMonth()->startOfMonth();
        $giorno = $this->mese->copy()->addDays(9);
        $this->sera = GameSession::factory()->create(['title' => 'Tavolo della sera', 'played_at' => $giorno->copy()->setTime(21, 0)]);
        $this->pomeriggio = GameSession::factory()->create(['title' => 'Tavolo del pomeriggio', 'played_at' => $giorno->copy()->setTime(15, 0)]);
        $this->dopo = GameSession::factory()->create(['title' => 'Tavolo del giorno dopo', 'played_at' => $giorno->copy()->addDay()->setTime(21, 0)]);
    });

    it('mostra le sessioni del mese a chi non ha un account; chi è dentro va alle sessioni', function () {
        $this->get(route('guest-bookings.calendar', ['mese' => $this->mese->format('Y-m')]))
            ->assertOk()
            ->assertSeeInOrder(['Tavolo del pomeriggio', 'Tavolo della sera', 'Tavolo del giorno dopo'])
            ->assertSee('scegline una');

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guest-bookings.calendar'))
            ->assertRedirect(route('sessions.index'));
    });

    it('chiede più sessioni insieme con una sola email, e un clic le conferma tutte', function () {
        $this->post(route('guest-bookings.calendar.store'), richiestaOspite([
            'sessioni' => [$this->sera->id, $this->dopo->id],
            'social' => '@lia_ig',
            'note' => 'Mi piacerebbe un ladro',
        ]))->assertRedirect()->assertSessionHas('inviata');

        $posti = SessionBooking::where('guest_email', 'lia@example.com')->get();
        expect($posti)->toHaveCount(2)
            ->and($posti->every(fn ($p) => $p->status === SeatStatus::Unverified))->toBeTrue()
            ->and($posti->first()->guest_social)->toBe('@lia_ig')
            ->and($posti->first()->guest_note)->toBe('Mi piacerebbe un ladro');

        Notification::assertSentTimes(GuestBookingMail::class, 1);

        $this->get(route('guest-bookings.verify', $posti->first()->guest_token));

        expect(SessionBooking::where('guest_email', 'lia@example.com')->pluck('status')->unique()->all())->toBe([SeatStatus::Requested]);

        $this->get(route('guest-bookings.show', $posti->first()->guest_token))
            ->assertSee('Le tue altre richieste')
            ->assertSee('Tavolo del giorno dopo');
    });

    it('due sessioni lo stesso giorno no: non ci si sdoppia', function () {
        $this->post(route('guest-bookings.calendar.store'), richiestaOspite([
            'sessioni' => [$this->sera->id, $this->pomeriggio->id],
        ]))->assertSessionHas('error');

        expect(SessionBooking::count())->toBe(0);
        Notification::assertNothingSent();
    });

    it('un giorno già preso con un\'altra richiesta si salta, senza dirlo', function () {
        $this->post(route('guest-bookings.calendar.store'), richiestaOspite(['sessioni' => [$this->sera->id]]));
        $this->get(route('guest-bookings.verify', SessionBooking::firstOrFail()->guest_token));

        $this->post(route('guest-bookings.calendar.store'), richiestaOspite(['sessioni' => [$this->pomeriggio->id, $this->dopo->id]]))
            ->assertSessionHas('inviata');

        expect(SessionBooking::where('game_session_id', $this->pomeriggio->id)->exists())->toBeFalse()
            ->and(SessionBooking::where('game_session_id', $this->dopo->id)->exists())->toBeTrue();
    });

    it('senza nessuna sessione scelta non parte niente', function () {
        $this->post(route('guest-bookings.calendar.store'), richiestaOspite())
            ->assertSessionHasErrors('sessioni');
    });

    it('i DM trovano il link del calendario nella pagina delle sessioni', function () {
        $this->actingAs($this->dm)->get(route('sessions.index'))->assertSee(route('guest-bookings.calendar', ['mese' => now()->format('Y-m')]));
        $this->actingAs(User::factory()->player()->create())->get(route('sessions.index'))->assertDontSee('Link del calendario');
    });
});
