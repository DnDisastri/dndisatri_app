<?php

declare(strict_types=1);

use App\Actions\Sessions\AnswerReserveQuestion;
use App\Actions\Sessions\AnswerSessionOffer;
use App\Actions\Sessions\ExpireSessionOffers;
use App\Actions\Sessions\OfferSessionSeat;
use App\Actions\Sessions\RequestSessionSeat;
use App\Actions\Sessions\WithdrawFromSession;
use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use App\Notifications\SessionReserveAsked;
use App\Notifications\SessionSeatOffered;
use App\Notifications\SessionSeatReleased;
use Illuminate\Support\Facades\Notification;

function giocatoreConPg(?string $nome = null): Character
{
    return Character::factory()->for(User::factory()->player()->create(array_filter(['name' => $nome])))->create();
}

function postoDi(GameSession $sessione, Character $pg): SessionBooking
{
    return $sessione->bookings()->where('user_id', $pg->user_id)->firstOrFail();
}

function chiedi(GameSession $sessione, Character $pg): SeatStatus
{
    return app(RequestSessionSeat::class)->handle($sessione->fresh(), $pg->user, $pg);
}

function offri(GameSession $sessione, Character $pg): SessionBooking
{
    return app(OfferSessionSeat::class)->handle($sessione->fresh(), postoDi($sessione, $pg));
}

function conferma(GameSession $sessione, Character $pg): SeatStatus
{
    return app(AnswerSessionOffer::class)->handle(postoDi($sessione, $pg), true);
}

beforeEach(fn () => Notification::fake());

describe('chiedere un posto', function () {
    it('non occupa un posto: è una richiesta, col personaggio scelto', function () {
        $sessione = GameSession::factory()->upcoming()->seats(3)->create();
        $pg = giocatoreConPg();

        expect(chiedi($sessione, $pg))->toBe(SeatStatus::Requested)
            ->and($sessione->fresh()->freeSlots())->toBe(3)
            ->and($sessione->fresh()->bookedCharacterOf($pg->user))->toBe($pg->id);
    });

    it('si può chiedere anche a posti pieni', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $primo = giocatoreConPg();
        chiedi($sessione, $primo);
        offri($sessione, $primo);

        expect(chiedi($sessione, giocatoreConPg()))->toBe(SeatStatus::Requested);
    });

    it('chiedere di nuovo cambia solo il personaggio', function () {
        $sessione = GameSession::factory()->upcoming()->seats(3)->create();
        $pg = giocatoreConPg();
        $altro = Character::factory()->for($pg->user)->create();

        chiedi($sessione, $pg);
        offri($sessione, $pg);
        conferma($sessione, $pg);
        chiedi($sessione, $altro);

        $sessione = $sessione->fresh();
        expect($sessione->seatOf($pg->user))->toBe(SeatStatus::Confirmed)
            ->and($sessione->bookedCharacterOf($pg->user))->toBe($altro->id);
    });

    it('non con il personaggio di un altro, e non a sessione cominciata', function () {
        $sessione = GameSession::factory()->upcoming()->create();

        expect(fn () => app(RequestSessionSeat::class)->handle($sessione, User::factory()->player()->create(), giocatoreConPg()))
            ->toThrow(SessionUnavailableException::class)
            ->and(fn () => chiedi(GameSession::factory()->create(), giocatoreConPg()))
            ->toThrow(SessionUnavailableException::class);
    });
});

describe('il DM offre un posto', function () {
    it('a chi vuole, anche fuori ordine, con 24 ore per confermare e un\'email', function () {
        $sessione = GameSession::factory()->upcoming()->seats(2)->create(['played_at' => now()->addWeek()]);
        $primo = giocatoreConPg();
        $secondo = giocatoreConPg();
        chiedi($sessione, $primo);
        chiedi($sessione, $secondo);

        $posto = offri($sessione, $secondo);

        expect($posto->status)->toBe(SeatStatus::Offered)
            ->and($posto->offer_expires_at->diffInHours(now(), true))->toEqualWithDelta(24, 0.1)
            ->and($sessione->fresh()->freeSlots())->toBe(1)
            ->and($sessione->fresh()->seatOf($primo->user))->toBe(SeatStatus::Requested);

        Notification::assertSentTo($secondo->user, SessionSeatOffered::class, fn ($n, array $canali) => in_array('mail', $canali, true));
    });

    it('la scadenza non va oltre l\'inizio della sessione', function () {
        $sessione = GameSession::factory()->upcoming()->create(['played_at' => now()->addHours(5)]);
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);

        expect(offri($sessione, $pg)->offer_expires_at->equalTo($sessione->played_at))->toBeTrue();
    });

    it('l\'email arriva anche a chi ha spento le notifiche', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $pg = giocatoreConPg();
        $pg->user->forceFill(['muted_notifications' => ['tavolo']])->save();
        chiedi($sessione, $pg);

        offri($sessione, $pg);

        Notification::assertSentTo($pg->user, SessionSeatOffered::class, fn ($n, array $canali) => in_array('mail', $canali, true));
    });

    it('non offre più posti di quanti ce ne sono', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $primo = giocatoreConPg();
        $secondo = giocatoreConPg();
        chiedi($sessione, $primo);
        chiedi($sessione, $secondo);
        offri($sessione, $primo);

        expect(fn () => offri($sessione, $secondo))->toThrow(SessionUnavailableException::class);
    });
});

describe('rispondere all\'offerta', function () {
    it('confermare tiene il posto', function () {
        $sessione = GameSession::factory()->upcoming()->seats(3)->create();
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        expect(conferma($sessione, $pg))->toBe(SeatStatus::Confirmed)
            ->and($sessione->fresh()->confirmedCount())->toBe(1);
    });

    it('rinunciare libera il posto e lo dice al DM della campagna e agli admin', function () {
        $dm = User::factory()->dm()->create();
        $admin = User::factory()->admin()->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign(Campaign::factory()->runBy($dm)->create())->create();
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        app(AnswerSessionOffer::class)->handle(postoDi($sessione, $pg), false);

        expect($sessione->fresh()->seatOf($pg->user))->toBe(SeatStatus::Withdrawn);
        Notification::assertSentTo([$dm, $admin], SessionSeatReleased::class);
    });

    it('dopo la scadenza non si conferma più', function () {
        $sessione = GameSession::factory()->upcoming()->create(['played_at' => now()->addWeek()]);
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        $this->travel(25)->hours();

        expect(fn () => conferma($sessione, $pg))->toThrow(SessionUnavailableException::class);
    });

    it('il comando fa scadere le offerte e avvisa il DM; il DM può offrire di nuovo', function () {
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign(Campaign::factory()->runBy($dm)->create())->create(['played_at' => now()->addWeek()]);
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        $this->travel(25)->hours();
        $this->artisan('dndisastri:scadi-offerte')->assertSuccessful();

        expect($sessione->fresh()->seatOf($pg->user))->toBe(SeatStatus::Expired)
            ->and($sessione->fresh()->freeSlots())->toBe($sessione->fresh()->max_players);
        Notification::assertSentTo($dm, SessionSeatReleased::class);

        expect(offri($sessione, $pg)->status)->toBe(SeatStatus::Offered);
    });

    it('le offerte ancora valide non scadono', function () {
        $sessione = GameSession::factory()->upcoming()->create(['played_at' => now()->addWeek()]);
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        expect(app(ExpireSessionOffers::class)->handle())->toBe(0);
    });
});

describe('le riserve', function () {
    it('a posti tutti confermati, chi non è stato scelto riceve la domanda, una volta sola', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $scelto = giocatoreConPg();
        $fuori = giocatoreConPg();
        chiedi($sessione, $scelto);
        chiedi($sessione, $fuori);
        offri($sessione, $scelto);

        Notification::assertNothingSentTo($fuori->user);

        conferma($sessione, $scelto);

        Notification::assertSentToTimes($fuori->user, SessionReserveAsked::class, 1);
        expect(postoDi($sessione, $fuori)->awaitsReserveAnswer())->toBeTrue();
    });

    it('chi dice sì diventa riserva, chi dice no si ritira', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $scelto = giocatoreConPg();
        $resta = giocatoreConPg();
        $esce = giocatoreConPg();
        foreach ([$scelto, $resta, $esce] as $pg) {
            chiedi($sessione, $pg);
        }
        offri($sessione, $scelto);
        conferma($sessione, $scelto);

        expect(app(AnswerReserveQuestion::class)->handle(postoDi($sessione, $resta), true))->toBe(SeatStatus::Reserve)
            ->and(app(AnswerReserveQuestion::class)->handle(postoDi($sessione, $esce), false))->toBe(SeatStatus::Withdrawn);
    });

    it('senza la domanda non si risponde', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);

        expect(fn () => app(AnswerReserveQuestion::class)->handle(postoDi($sessione, $pg), true))
            ->toThrow(SessionUnavailableException::class);
    });

    it('se un confermato si ritira, il DM e gli admin lo sanno con le riserve; nessuno entra da solo', function () {
        $dm = User::factory()->dm()->create();
        $admin = User::factory()->admin()->create();
        $sessione = GameSession::factory()->upcoming()->seats(1)->inCampaign(Campaign::factory()->runBy($dm)->create())->create();
        $scelto = giocatoreConPg();
        $riserva = giocatoreConPg('Rita Riserva');
        chiedi($sessione, $scelto);
        chiedi($sessione, $riserva);
        offri($sessione, $scelto);
        conferma($sessione, $scelto);
        app(AnswerReserveQuestion::class)->handle(postoDi($sessione, $riserva), true);

        app(WithdrawFromSession::class)->handle(postoDi($sessione, $scelto));

        expect($sessione->fresh()->seatOf($riserva->user))->toBe(SeatStatus::Reserve)
            ->and($sessione->fresh()->freeSlots())->toBe(1);

        Notification::assertSentTo($dm, SessionSeatReleased::class, fn (SessionSeatReleased $n) => str_contains($n->toArray($dm)['body'], 'Rita Riserva'));
        Notification::assertSentTo($admin, SessionSeatReleased::class);
    });

    it('ritirare una semplice richiesta non avvisa nessuno', function () {
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign(Campaign::factory()->runBy($dm)->create())->create();
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);

        app(WithdrawFromSession::class)->handle(postoDi($sessione, $pg));

        Notification::assertNotSentTo($dm, SessionSeatReleased::class);
        expect(chiedi($sessione, $pg))->toBe(SeatStatus::Requested)
            ->and($sessione->fresh()->players()->count())->toBe(1);
    });
});

describe('permessi', function () {
    it('il giocatore chiede e si ritira; il DM della campagna non chiede', function () {
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign(Campaign::factory()->runBy($dm)->create())->create();
        $pg = giocatoreConPg();

        expect($pg->user->can('book', $sessione))->toBeTrue()
            ->and($pg->user->can('withdraw', $sessione))->toBeFalse()
            ->and($dm->can('book', $sessione))->toBeFalse();

        chiedi($sessione, $pg);

        expect($pg->user->can('book', $sessione->fresh()))->toBeFalse()
            ->and($pg->user->can('withdraw', $sessione->fresh()))->toBeTrue();
    });

    it('i posti li offre un DM, non un giocatore', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $pg = giocatoreConPg();
        chiedi($sessione, $pg);

        $this->actingAs(User::factory()->player()->create())
            ->post(route('sessions.offer', $sessione), ['booking_id' => postoDi($sessione, $pg)->id])
            ->assertForbidden();
    });
});

describe('la pagina della sessione', function () {
    it('il giocatore chiede il posto, e gli altri non vedono le richieste', function () {
        $sessione = GameSession::factory()->upcoming()->seats(4)->create();
        $pg = giocatoreConPg('Marta Richiesta');

        $this->actingAs($pg->user)->get(route('sessions.show', $sessione))
            ->assertOk()
            ->assertSee('Chi gioca')
            ->assertSee('Chiedo un posto');

        $this->actingAs($pg->user)->post(route('sessions.book', $sessione), ['character_id' => $pg->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($pg->user)->get(route('sessions.show', $sessione))->assertSee('Richiesta inviata');

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $sessione))
            ->assertDontSee('Marta Richiesta')
            ->assertSee('0 / 4 confermati');
    });

    it('il DM vede le richieste in ordine di arrivo e offre il posto', function () {
        $sessione = GameSession::factory()->upcoming()->seats(2)->create();
        $primo = giocatoreConPg('Arrivato Prima');
        $secondo = giocatoreConPg('Arrivato Dopo');
        chiedi($sessione, $primo);
        $this->travel(1)->minutes();
        chiedi($sessione, $secondo);
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm)->get(route('sessions.show', $sessione))
            ->assertSeeInOrder(['Arrivato Prima', 'Arrivato Dopo'])
            ->assertSee('Offri il posto')
            ->assertSee(route('guest-bookings.create', $sessione));

        $this->actingAs($dm)->post(route('sessions.offer', $sessione), ['booking_id' => postoDi($sessione, $secondo)->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        expect($sessione->fresh()->seatOf($secondo->user))->toBe(SeatStatus::Offered);
    });

    it('il giocatore scelto conferma dalla pagina, e il suo nome compare a tutti', function () {
        $sessione = GameSession::factory()->upcoming()->seats(2)->create();
        $pg = giocatoreConPg('Marta Scelta');
        chiedi($sessione, $pg);
        offri($sessione, $pg);

        $this->actingAs($pg->user)->get(route('sessions.show', $sessione))->assertSee('Confermo il posto');
        $this->actingAs($pg->user)->post(route('sessions.answer-offer', $sessione), ['risposta' => 'si'])->assertRedirect();

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $sessione))
            ->assertSee('Marta Scelta')
            ->assertSee('1 / 2 confermati');
    });

    it('le presenze partono dai confermati', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $pg = giocatoreConPg('Marta');
        chiedi($sessione, $pg);
        offri($sessione, $pg);
        conferma($sessione, $pg);

        $html = $this->actingAs(User::factory()->dm()->create())->get(route('sessions.show', $sessione))->getContent();

        expect($html)->toMatch('/value="'.$pg->user_id.'"\s+checked/')
            ->and($html)->toMatch('/value="'.$pg->id.'"\s+selected/');
    });

    it('gli altri giocatori delle presenze si cercano: nascosti finché non li cerchi', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $confermato = giocatoreConPg('Marta Confermata');
        chiedi($sessione, $confermato);
        offri($sessione, $confermato);
        conferma($sessione, $confermato);
        giocatoreConPg('Ugo Nonprenotato');

        $html = $this->actingAs(User::factory()->dm()->create())->get(route('sessions.show', $sessione))->getContent();

        expect($html)->toContain('data-presenze-cerca')
            ->and($html)->toMatch('/data-presenza-extra="ugo nonprenotato"\s+hidden/')
            ->and($html)->not->toContain('data-presenza-extra="marta confermata"');
    });
});

describe('dal calendario e da «Le mie prenotazioni»', function () {
    it('chiede il posto a più sessioni insieme, con lo stesso eroe', function () {
        $pg = giocatoreConPg();
        $prima = GameSession::factory()->upcoming()->create(['played_at' => now()->addDays(3)->setTime(21, 0)]);
        $seconda = GameSession::factory()->upcoming()->create(['played_at' => now()->addDays(5)->setTime(21, 0)]);

        $this->actingAs($pg->user)
            ->post(route('sessions.book-many'), ['sessioni' => [$prima->id, $seconda->id], 'character_id' => $pg->id])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'per 2 sessioni'));

        expect($prima->fresh()->seatOf($pg->user))->toBe(SeatStatus::Requested)
            ->and($seconda->fresh()->bookedCharacterOf($pg->user))->toBe($pg->id);
    });

    it('una sessione al giorno: due lo stesso giorno no, e un giorno già preso non si raddoppia', function () {
        $pg = giocatoreConPg();
        $sera = GameSession::factory()->upcoming()->create(['played_at' => now()->addDays(3)->setTime(21, 0)]);
        $pomeriggio = GameSession::factory()->upcoming()->create(['played_at' => now()->addDays(3)->setTime(15, 0)]);

        $this->actingAs($pg->user)
            ->post(route('sessions.book-many'), ['sessioni' => [$sera->id, $pomeriggio->id], 'character_id' => $pg->id])
            ->assertSessionHas('error');
        expect($pg->user->sessionBookings()->count())->toBe(0);

        chiedi($sera, $pg);

        expect(fn () => chiedi($pomeriggio, $pg))->toThrow(SessionUnavailableException::class);
    });

    it('non con l\'eroe di un altro', function () {
        $pg = giocatoreConPg();
        $sessione = GameSession::factory()->upcoming()->create();

        $this->actingAs(User::factory()->player()->create())
            ->post(route('sessions.book-many'), ['sessioni' => [$sessione->id], 'character_id' => $pg->id])
            ->assertSessionHas('error');

        expect($sessione->bookings()->count())->toBe(0);
    });

    it('raccoglie le prenotazioni, con la risposta all\'offerta a portata di mano', function () {
        $pg = giocatoreConPg();
        $offerta = GameSession::factory()->upcoming()->create(['title' => 'Il tavolo offerto', 'played_at' => now()->addDays(2)]);
        $richiesta = GameSession::factory()->upcoming()->create(['title' => 'Il tavolo chiesto', 'played_at' => now()->addDays(4)]);
        chiedi($offerta, $pg);
        offri($offerta, $pg);
        chiedi($richiesta, $pg);

        $this->actingAs($pg->user)->get(route('sessions.mine'))
            ->assertOk()
            ->assertSeeInOrder(['confermalo', 'Il tavolo offerto', 'Confermo il posto', 'Richieste inviate', 'Il tavolo chiesto']);

        $this->actingAs($pg->user)
            ->from(route('sessions.mine'))
            ->post(route('sessions.answer-offer', $offerta), ['risposta' => 'si'])
            ->assertRedirect(route('sessions.mine'));

        expect($offerta->fresh()->seatOf($pg->user))->toBe(SeatStatus::Confirmed);
    });
});
