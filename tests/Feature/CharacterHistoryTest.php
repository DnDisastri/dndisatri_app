<?php

declare(strict_types=1);

use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RetireCharacter;
use App\Actions\Characters\TransferSessions;
use App\Actions\Sessions\RecordAttendance;
use App\Domain\Dnd\Coins;
use App\Enums\PendingChangeStatus;
use App\Enums\SeatStatus;
use App\Livewire\DmTools;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Anna']);
    $this->dm = User::factory()->dm()->create();

    $this->sessione = GameSession::factory()->create(['title' => 'La torre', 'played_at' => now()->subWeek()]);
    app(RecordAttendance::class)->handle($this->sessione, [$this->giocatore->id => $this->pg->id]);
});

describe('il ritiro del personaggio', function () {
    it('sparisce dall\'app ma resta nel database, e le sue richieste si rifiutano', function () {
        $richiesta = app(ProposeChange::class)->loot($this->pg, $this->giocatore, coins: new Coins(gp: 10));

        app(RetireCharacter::class)->handle($this->pg, $this->dm);

        expect(Character::find($this->pg->id))->toBeNull()
            ->and(Character::withTrashed()->find($this->pg->id)->retired_by)->toBe($this->dm->id)
            ->and($richiesta->fresh()->status)->toBe(PendingChangeStatus::Rejected);

        $this->actingAs($this->dm)->get(route('characters.show', $this->pg))->assertNotFound();
        // Lo storico continua a vederlo.
        expect($richiesta->fresh()->character->name)->toBe('Anna');
    });

    it('non si ritira con un posto in una sessione futura', function () {
        $futura = GameSession::factory()->upcoming()->create();
        SessionBooking::forceCreate([
            'game_session_id' => $futura->id, 'user_id' => $this->giocatore->id,
            'character_id' => $this->pg->id, 'status' => SeatStatus::Confirmed->value,
        ]);

        expect(fn () => app(RetireCharacter::class)->handle($this->pg, $this->dm))
            ->toThrow(RuntimeException::class, 'un posto in una sessione futura');
    });

    it('lo fa un DM dalla scheda; un giocatore no', function () {
        Livewire::actingAs($this->giocatore)
            ->test(DmTools::class, ['character' => $this->pg])
            ->call('apriRitiro')
            ->assertForbidden();

        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->call('apriRitiro')
            ->call('ritira')
            ->assertHasErrors('ritiroCapito')
            ->set('ritiroCapito', true)
            ->call('ritira')
            ->assertRedirect(route('characters.index'));

        expect($this->pg->fresh()?->trashed() ?? true)->toBeTrue();
    });
});

describe('il passaggio delle sessioni', function () {
    it('le presenze passano al nuovo personaggio dello stesso giocatore', function () {
        app(RetireCharacter::class)->handle($this->pg, $this->dm);
        $nuovo = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Bea']);

        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $nuovo])
            ->assertSee('Sessioni da un ritirato')
            ->call('apriPassaggio')
            ->set('passaggioDa', $this->pg->id)
            ->call('passaSessioni')
            ->assertRedirect(route('characters.show', $nuovo));

        expect($nuovo->sessions()->pluck('game_sessions.id')->all())->toBe([$this->sessione->id]);
    });

    it('non passano a un personaggio di un altro giocatore', function () {
        app(RetireCharacter::class)->handle($this->pg, $this->dm);
        $estraneo = Character::factory()->create();

        expect(fn () => app(TransferSessions::class)->handle(Character::withTrashed()->find($this->pg->id), $estraneo, $this->dm))
            ->toThrow(RuntimeException::class);
    });
});
