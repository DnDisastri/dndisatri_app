<?php

declare(strict_types=1);

use App\Actions\Sessions\RecordAttendance;
use App\Enums\SeatStatus;
use App\Enums\SpecialSessionType;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\SessionTable;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->speciale = Campaign::factory()->special()->create();
    $this->dm = User::factory()->dm()->create(['name' => 'Aurelio']);
    $this->altroDm = User::factory()->dm()->create(['name' => 'Morgana']);
});

describe('dal pannello', function () {
    it('una multitavolo si crea con tipo, stagione e tavoli', function () {
        $this->actingAs($this->dm);

        Livewire::test(CreateGameSession::class)
            ->fillForm([
                'campaign_id' => $this->speciale->id,
                'special_type' => SpecialSessionType::Opening->value,
                'season' => 3,
                'title' => 'Il risveglio',
                'played_at' => now()->subWeeks(2),
            ])
            ->set('data.tables', [
                ['name' => 'Tavolo 1', 'seats' => 5, 'dms' => [$this->dm->id, $this->altroDm->id]],
                ['name' => 'Tavolo 2', 'seats' => 5, 'dms' => [$this->altroDm->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sessione = GameSession::with('tables.dms')->first();

        expect($sessione->special_type)->toBe(SpecialSessionType::Opening)
            ->and($sessione->season)->toBe(3)
            ->and($sessione->tables)->toHaveCount(2)
            ->and($sessione->max_players)->toBe(10)
            ->and($sessione->tables->first()->dms->pluck('name')->all())->toBe(['Aurelio', 'Morgana']);
    });

    it('spostata in una campagna normale perde tipo e stagione', function () {
        $sessione = GameSession::factory()->inCampaign($this->speciale)->create([
            'special_type' => SpecialSessionType::Closing, 'season' => 2,
        ]);
        $normale = Campaign::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditGameSession::class, ['record' => $sessione->getRouteKey()])
            ->fillForm(['campaign_id' => $normale->id])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($sessione->fresh()->special_type)->toBeNull()
            ->and($sessione->fresh()->season)->toBeNull();
    });

    it('salvando dal pannello i presenti restano al loro tavolo', function () {
        $sessione = GameSession::factory()->inCampaign($this->speciale)->create([
            'played_at' => now()->subDay(), 'special_type' => SpecialSessionType::Opening, 'season' => 3,
        ]);
        $tavolo = SessionTable::factory()->for($sessione, 'session')->create();
        $anna = Character::factory()->create();
        app(RecordAttendance::class)->handle($sessione, [$anna->user_id => $anna->id], [$anna->user_id => $tavolo->id]);

        Livewire::actingAs($this->dm)
            ->test(EditGameSession::class, ['record' => $sessione->getRouteKey()])
            ->fillForm(['title' => 'Il risveglio'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($sessione->attendees()->first()->pivot->session_table_id)->toBe($tavolo->id);
    });
});

it('i posti della sessione seguono i tavoli', function () {
    $sessione = GameSession::factory()->inCampaign($this->speciale)->create(['max_players' => 6, 'min_players' => 3]);

    SessionTable::factory()->for($sessione, 'session')->create(['seats' => 5]);
    SessionTable::factory()->for($sessione, 'session')->create(['seats' => 4]);

    expect($sessione->fresh()->max_players)->toBe(9);
});

it('chi conduce un tavolo conduce la sessione', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->speciale)->create();
    $tavolo = SessionTable::factory()->for($sessione, 'session')->create();
    $tavolo->dms()->attach($this->altroDm);

    expect($sessione->isRunBy($this->altroDm))->toBeTrue()
        ->and($this->altroDm->can('book', $sessione))->toBeFalse()
        ->and($sessione->conductors()->pluck('name')->all())->toBe(['Morgana']);
});

describe('nella pagina della sessione', function () {
    beforeEach(function () {
        $this->sessione = GameSession::factory()->upcoming()->inCampaign($this->speciale)->create();
        $this->tavolo1 = SessionTable::factory()->for($this->sessione, 'session')->create(['name' => 'Tavolo 1']);
        $this->tavolo2 = SessionTable::factory()->for($this->sessione, 'session')->create(['name' => 'Tavolo 2', 'position' => 1]);
        $this->tavolo2->dms()->attach($this->altroDm);

        $this->anna = Character::factory()->create(['name' => 'Anna la Rossa']);
        $this->posto = SessionBooking::forceCreate([
            'game_session_id' => $this->sessione->id,
            'user_id' => $this->anna->user_id,
            'character_id' => $this->anna->id,
            'status' => SeatStatus::Confirmed->value,
        ]);
    });

    it('un DM assegna i tavoli e il giocatore vede dove gioca', function () {
        $this->actingAs($this->dm)
            ->post(route('sessions.tables.assign', $this->sessione), ['tavoli' => [$this->posto->id => $this->tavolo2->id]])
            ->assertSessionHas('status', 'Tavoli assegnati.');

        expect($this->posto->fresh()->session_table_id)->toBe($this->tavolo2->id);

        $this->actingAs($this->anna->user)
            ->get(route('sessions.show', $this->sessione))
            ->assertOk()
            ->assertSee('I tavoli')
            ->assertSee('Sei al Tavolo 2, con Morgana.');
    });

    it('un giocatore non assegna i tavoli, e i tavoli di un\'altra sessione non valgono', function () {
        $this->actingAs($this->anna->user)
            ->post(route('sessions.tables.assign', $this->sessione), ['tavoli' => [$this->posto->id => $this->tavolo1->id]])
            ->assertForbidden();

        $estraneo = SessionTable::factory()->create();

        $this->actingAs($this->dm)
            ->post(route('sessions.tables.assign', $this->sessione), ['tavoli' => [$this->posto->id => $estraneo->id]])
            ->assertSessionHas('error');

        expect($this->posto->fresh()->session_table_id)->toBeNull();
    });

    it('dopo la sessione: presenze per tavolo, resoconto del tavolo e ricompense a un tavolo', function () {
        $this->sessione->forceFill(['played_at' => now()->subDay()])->save();
        $bruno = Character::factory()->create(['name' => 'Bruno']);

        $this->actingAs($this->dm)
            ->post(route('sessions.attendance', $this->sessione), [
                'presenti' => [$this->anna->user_id, $bruno->user_id],
                'personaggi' => [$this->anna->user_id => $this->anna->id, $bruno->user_id => $bruno->id],
                'tavoli' => [$this->anna->user_id => $this->tavolo1->id, $bruno->user_id => $this->tavolo2->id],
            ])
            ->assertSessionHas('status');

        $this->actingAs($this->dm)
            ->post(route('sessions.tables.recap', [$this->sessione, $this->tavolo1]), ['recap' => 'Il drago è caduto al primo round.'])
            ->assertSessionHas('status');

        $this->actingAs($this->dm)
            ->post(route('sessions.rewards', $this->sessione), [
                'coins' => ['gp' => 30], 'reason' => 'Tesoro del drago', 'destinatari' => 'tavolo-'.$this->tavolo1->id,
            ])
            ->assertSessionHas('status');

        // 15 mo di partenza dalla factory.
        expect($this->anna->refresh()->gp)->toBe(15 + 30)
            ->and($bruno->refresh()->gp)->toBe(15)
            ->and($this->tavolo1->fresh()->recap)->toBe('Il drago è caduto al primo round.')
            ->and($this->sessione->fresh()->rewards[0]['table'])->toBe('Tavolo 1');

        $this->actingAs(User::factory()->player()->create())
            ->get(route('sessions.show', $this->sessione))
            ->assertOk()
            ->assertSeeInOrder(['Tavolo 1', 'Anna la Rossa', 'Il drago è caduto al primo round.', 'Tavolo 2', 'Bruno']);
    });

    it('le ricompense vanno anche a un personaggio solo', function () {
        $this->sessione->forceFill(['played_at' => now()->subDay()])->save();
        $bruno = Character::factory()->create();
        app(RecordAttendance::class)->handle($this->sessione, [
            $this->anna->user_id => $this->anna->id, $bruno->user_id => $bruno->id,
        ]);

        $this->actingAs($this->dm)
            ->post(route('sessions.rewards', $this->sessione), [
                'coins' => ['gp' => 10], 'reason' => 'Prima a capire l\'enigma', 'destinatari' => 'pg-'.$this->anna->id,
            ])
            ->assertSessionHas('status');

        expect($this->anna->refresh()->gp)->toBe(15 + 10)
            ->and($bruno->refresh()->gp)->toBe(15);
    });

    it('il resoconto di un tavolo di un\'altra sessione non si scrive da qui', function () {
        $altro = SessionTable::factory()->create();

        $this->actingAs($this->dm)
            ->post(route('sessions.tables.recap', [$this->sessione, $altro]), ['recap' => 'No'])
            ->assertNotFound();
    });
});
