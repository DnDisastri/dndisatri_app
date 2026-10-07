<?php

declare(strict_types=1);

use App\Actions\Sessions\BookSessionSeat;
use App\Actions\Sessions\ConfirmSessionPlayers;
use App\Actions\Sessions\PromoteFromWaitingList;
use App\Actions\Sessions\WithdrawFromSession;
use App\Enums\SeatStatus;
use App\Exceptions\SessionUnavailableException;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use App\Notifications\SessionSeatConfirmed;
use Illuminate\Support\Facades\Notification;

function giocatoreConPg(?string $nome = null): Character
{
    return Character::factory()->for(User::factory()->player()->create(array_filter(['name' => $nome])))->create();
}

function postoDi(GameSession $sessione, Character $pg): SessionBooking
{
    return $sessione->bookings()->where('user_id', $pg->user_id)->firstOrFail();
}

function prenota(GameSession $sessione, Character $pg): SeatStatus
{
    return app(BookSessionSeat::class)->handle($sessione->fresh(), $pg->user, $pg);
}

describe('prenotarsi a una sessione', function () {
    it('occupa un posto, con il personaggio scelto', function () {
        $sessione = GameSession::factory()->upcoming()->seats(3)->create();
        $pg = giocatoreConPg();

        expect(prenota($sessione, $pg))->toBe(SeatStatus::Booked)
            ->and($sessione->fresh()->freeSlots())->toBe(2)
            ->and($sessione->fresh()->bookedCharacterOf($pg->user))->toBe($pg->id);
    });

    it('a posti esauriti si entra in lista d\'attesa', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        prenota($sessione, giocatoreConPg());

        expect(prenota($sessione, giocatoreConPg()))->toBe(SeatStatus::Waiting)
            ->and($sessione->fresh()->participantCount())->toBe(1)
            ->and($sessione->fresh()->bookings()->waiting()->count())->toBe(1);
    });

    it('prenotarsi di nuovo cambia solo il personaggio', function () {
        $sessione = GameSession::factory()->upcoming()->seats(3)->create();
        $pg = giocatoreConPg();
        $altro = Character::factory()->for($pg->user)->create();

        prenota($sessione, $pg);
        app(ConfirmSessionPlayers::class)->handle($sessione->fresh());
        prenota($sessione, $altro);

        $sessione = $sessione->fresh();
        expect($sessione->participantCount())->toBe(1)
            ->and($sessione->seatOf($pg->user))->toBe(SeatStatus::Confirmed)
            ->and($sessione->bookedCharacterOf($pg->user))->toBe($altro->id);
    });

    it('non con il personaggio di un altro', function () {
        $sessione = GameSession::factory()->upcoming()->create();

        expect(fn () => app(BookSessionSeat::class)->handle($sessione, User::factory()->player()->create(), giocatoreConPg()))
            ->toThrow(SessionUnavailableException::class);
    });

    it('a sessione cominciata le prenotazioni sono chiuse', function () {
        $sessione = GameSession::factory()->create();

        expect(fn () => prenota($sessione, giocatoreConPg()))->toThrow(SessionUnavailableException::class);
    });

    it('ritirarsi libera il posto e lascia la traccia; si può ripensarci', function () {
        $sessione = GameSession::factory()->upcoming()->seats(2)->create();
        $pg = giocatoreConPg();

        prenota($sessione, $pg);
        app(WithdrawFromSession::class)->handle($sessione->fresh(), $pg->user);

        expect($sessione->fresh()->freeSlots())->toBe(2)
            ->and($sessione->fresh()->seatOf($pg->user))->toBe(SeatStatus::Withdrawn);

        expect(prenota($sessione, $pg))->toBe(SeatStatus::Booked)
            ->and($sessione->fresh()->players()->count())->toBe(1);
    });
});

describe('«la sessione si fa»', function () {
    it('conferma tutti i prenotati insieme, li avvisa e non tocca la lista d\'attesa', function () {
        Notification::fake();

        $sessione = GameSession::factory()->upcoming()->seats(2, min: 4)->create();
        [$primo, $secondo, $terzo] = [giocatoreConPg(), giocatoreConPg(), giocatoreConPg()];
        prenota($sessione, $primo);
        prenota($sessione, $secondo);
        prenota($sessione, $terzo);

        // Sotto il minimo si conferma lo stesso: decide il DM.
        app(ConfirmSessionPlayers::class)->handle($sessione->fresh());
        $sessione = $sessione->fresh();

        expect($sessione->isConfirmed())->toBeTrue()
            ->and($sessione->seatOf($primo->user))->toBe(SeatStatus::Confirmed)
            ->and($sessione->seatOf($terzo->user))->toBe(SeatStatus::Waiting);

        Notification::assertSentTo([$primo->user, $secondo->user], SessionSeatConfirmed::class);
        Notification::assertNotSentTo($terzo->user, SessionSeatConfirmed::class);
    });
});

describe('la lista d\'attesa', function () {
    it('tiene l\'ordine di arrivo', function () {
        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        prenota($sessione, giocatoreConPg());

        prenota($sessione, giocatoreConPg('Arrivato prima'));
        $this->travel(1)->minutes();
        prenota($sessione, giocatoreConPg('Arrivato dopo'));

        expect($sessione->fresh()->bookings()->waiting()->with('user')->get()->map->displayName()->all())->toBe(['Arrivato prima', 'Arrivato dopo']);
    });

    it('si pesca solo con un posto libero, e a sessione confermata si entra confermati', function () {
        Notification::fake();

        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $primo = giocatoreConPg();
        $inAttesa = giocatoreConPg();
        prenota($sessione, $primo);
        prenota($sessione, $inAttesa);

        expect(fn () => app(PromoteFromWaitingList::class)->handle($sessione->fresh(), postoDi($sessione, $inAttesa)))
            ->toThrow(SessionUnavailableException::class);

        app(ConfirmSessionPlayers::class)->handle($sessione->fresh());
        app(WithdrawFromSession::class)->handle($sessione->fresh(), $primo->user);

        expect(app(PromoteFromWaitingList::class)->handle($sessione->fresh(), postoDi($sessione, $inAttesa)))->toBe(SeatStatus::Confirmed);
        Notification::assertSentTo($inAttesa->user, SessionSeatConfirmed::class);
    });
});

describe('permessi', function () {
    it('il giocatore si prenota e si ritira; il DM della campagna non si prenota', function () {
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign(Campaign::factory()->runBy($dm)->create())->create();
        $pg = giocatoreConPg();

        expect($pg->user->can('book', $sessione))->toBeTrue()
            ->and($pg->user->can('withdraw', $sessione))->toBeFalse()
            ->and($dm->can('book', $sessione))->toBeFalse();

        prenota($sessione, $pg);

        expect($pg->user->can('book', $sessione->fresh()))->toBeFalse()
            ->and($pg->user->can('withdraw', $sessione->fresh()))->toBeTrue();
    });

    it('confermare spetta a un DM, e solo se c\'è qualcuno da confermare', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $dm = User::factory()->dm()->create();

        expect($dm->can('confirmPlayers', $sessione))->toBeFalse();

        prenota($sessione, giocatoreConPg());

        expect($dm->can('confirmPlayers', $sessione->fresh()))->toBeTrue()
            ->and(User::factory()->player()->create()->can('confirmPlayers', $sessione->fresh()))->toBeFalse();
    });
});

describe('la pagina della sessione', function () {
    it('il giocatore si prenota scegliendo il personaggio', function () {
        $sessione = GameSession::factory()->upcoming()->seats(4)->create();
        $pg = giocatoreConPg();

        $this->actingAs($pg->user)->get(route('sessions.show', $sessione))
            ->assertOk()
            ->assertSee('Chi gioca')
            ->assertSee('Mi prenoto')
            ->assertSee($pg->name);

        $this->actingAs($pg->user)->post(route('sessions.book', $sessione), ['character_id' => $pg->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $sessione))
            ->assertSee($pg->user->name)
            ->assertSee('1 / 4 posti');
    });

    it('il DM conferma e chiama dalla lista d\'attesa', function () {
        Notification::fake();

        $sessione = GameSession::factory()->upcoming()->seats(1)->create();
        $primo = giocatoreConPg();
        $inAttesa = giocatoreConPg();
        prenota($sessione, $primo);
        prenota($sessione, $inAttesa);
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm)->post(route('sessions.confirm', $sessione))->assertRedirect();
        $this->actingAs($primo->user)->post(route('sessions.withdraw', $sessione))->assertRedirect();
        $this->actingAs($dm)->post(route('sessions.promote', $sessione), ['booking_id' => postoDi($sessione, $inAttesa)->id])->assertRedirect();

        expect($sessione->fresh()->seatOf($inAttesa->user))->toBe(SeatStatus::Confirmed);
    });

    it('le presenze partono dai prenotati', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $pg = giocatoreConPg('Marta');
        prenota($sessione, $pg);

        $html = $this->actingAs(User::factory()->dm()->create())->get(route('sessions.show', $sessione))->getContent();

        expect($html)->toMatch('/value="'.$pg->user_id.'"\s+checked/')
            ->and($html)->toMatch('/value="'.$pg->id.'"\s+selected/');
    });
});
