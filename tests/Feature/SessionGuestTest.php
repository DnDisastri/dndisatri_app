<?php

declare(strict_types=1);

use App\Actions\Sessions\AddGuest;
use App\Actions\Sessions\BookSessionSeat;
use App\Actions\Sessions\ConfirmSessionPlayers;
use App\Actions\Sessions\LinkGuest;
use App\Enums\SeatStatus;
use App\Livewire\CombatTracker;
use App\Models\Character;
use App\Models\Encounter;
use App\Models\GameSession;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->dm = User::factory()->dm()->create();
    $this->sessione = GameSession::factory()->upcoming()->seats(2)->create();
});

it('un DM qualsiasi aggiunge un ospite, che occupa un posto e si vede col nome', function () {
    $this->actingAs($this->dm)->post(route('sessions.guests.store', $this->sessione), [
        'guest_name' => 'Marco da Instagram',
        'guest_character' => 'Pregenerato guerriero',
        'guest_note' => '@marco',
    ])->assertRedirect()->assertSessionHas('status');

    expect($this->sessione->fresh()->participantCount())->toBe(1);

    $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $this->sessione))
        ->assertOk()
        ->assertSee('Marco da Instagram')
        ->assertSee('ospite')
        ->assertSee('Pregenerato guerriero')
        ->assertDontSee('@marco');
});

it('un giocatore non aggiunge ospiti', function () {
    $this->actingAs(User::factory()->player()->create())
        ->post(route('sessions.guests.store', $this->sessione), ['guest_name' => 'Intruso'])
        ->assertForbidden();
});

it('a posti esauriti l\'ospite va in lista d\'attesa, e chi arriva dopo pure', function () {
    app(AddGuest::class)->handle($this->sessione, $this->dm, 'Primo');
    app(AddGuest::class)->handle($this->sessione->fresh(), $this->dm, 'Secondo');
    $terzo = app(AddGuest::class)->handle($this->sessione->fresh(), $this->dm, 'Terzo');

    $pg = Character::factory()->for(User::factory()->player())->create();

    expect($terzo->status)->toBe(SeatStatus::Waiting)
        ->and(app(BookSessionSeat::class)->handle($this->sessione->fresh(), $pg->user, $pg))->toBe(SeatStatus::Waiting);
});

it('la conferma vale anche per gli ospiti', function () {
    $ospite = app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco');

    app(ConfirmSessionPlayers::class)->handle($this->sessione->fresh());

    expect($ospite->fresh()->status)->toBe(SeatStatus::Confirmed);
});

it('si toglie e libera il posto', function () {
    $ospite = app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco');

    $this->actingAs($this->dm)->post(route('sessions.guests.remove', [$this->sessione, $ospite->id]))->assertRedirect();

    expect($ospite->fresh()->status)->toBe(SeatStatus::Withdrawn)
        ->and($this->sessione->fresh()->participantCount())->toBe(0);
});

it('quando si registra, il posto passa al suo account', function () {
    $ospite = app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco');
    $marco = User::factory()->player()->create();

    $this->actingAs($this->dm)
        ->post(route('sessions.guests.link', [$this->sessione, $ospite->id]), ['user_id' => $marco->id])
        ->assertRedirect()
        ->assertSessionHas('status');

    $sessione = $this->sessione->fresh();
    expect($sessione->seatOf($marco))->toBe(SeatStatus::Booked)
        ->and($sessione->participantCount())->toBe(1)
        ->and($ospite->fresh()->isGuest())->toBeFalse();
});

it('non si collega a chi è già prenotato', function () {
    $ospite = app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco');
    $pg = Character::factory()->for(User::factory()->player())->create();
    app(BookSessionSeat::class)->handle($this->sessione->fresh(), $pg->user, $pg);

    expect(fn () => app(LinkGuest::class)->handle($ospite, $pg->user))->toThrow(InvalidArgumentException::class);
});

it('presente a sessione finita, e collegato dopo, la presenza passa a lui', function () {
    $ospite = app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco');
    $this->sessione->forceFill(['played_at' => now()->subHour()])->save();

    $this->actingAs($this->dm)->post(route('sessions.attendance', $this->sessione), ['ospiti' => [$ospite->id]])->assertRedirect();
    expect($ospite->fresh()->guest_attended)->toBeTrue();

    $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $this->sessione))
        ->assertSee('Chi c\'era', false)
        ->assertSee('Marco');

    $marco = User::factory()->player()->create();
    app(LinkGuest::class)->handle($ospite->fresh(), $marco);

    expect($this->sessione->fresh()->attended($marco))->toBeTrue();
});

it('il combattimento mette in fila anche gli ospiti, con PF e CA a mano', function () {
    app(AddGuest::class)->handle($this->sessione, $this->dm, 'Marco', 'Brunilde');
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

    $ospite = collect($scontro->fresh()->combatants)->firstWhere('tipo', 'ospite');
    expect($ospite['hpMax'])->toBe(20)->and($ospite['hp'])->toBe(20)->and($ospite['ac'])->toBe(15);
});
