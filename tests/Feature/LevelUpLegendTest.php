<?php

declare(strict_types=1);

use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\PendingChange;
use App\Models\User;

// `created_at` è il punto di partenza per chi non ha ancora nessun level-up approvato.
beforeEach(function () {
    $this->owner = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->owner)->create();
    $this->pg->forceFill(['created_at' => now()->subMonth()])->save();
});

function presenza(Character $pg, User $owner, GameSession $session): void
{
    $pg->sessions()->attach($session, ['user_id' => $owner->getKey()]);
}

it('conta le sessioni giocate da chi non è mai salito, dalla creazione', function () {
    presenza($this->pg, $this->owner, GameSession::factory()->playedOn(now()->subWeeks(2))->create());
    presenza($this->pg, $this->owner, GameSession::factory()->playedOn(now()->subDays(3))->create());

    expect($this->pg->sessionsSinceLastLevelUp())->toBe(2)
        ->and($this->pg->canRequestLevelUp())->toBeTrue();
});

it('non conta le sessioni giocate prima dell\'ultimo passaggio di livello', function () {

    $change = $this->pg->pendingChanges()->create([
        'requested_by' => $this->owner->getKey(),
        'type' => PendingChangeType::LevelUp,
        'diff' => ['level' => 4],
        'summary' => 'Sale al 4',
    ]);
    $change->forceFill(['status' => PendingChangeStatus::Approved])->save();
// La data dell'ultimo level-up approvato è il confine da cui contare le sessioni successive.
    PendingChange::whereKey($change->getKey())->update(['updated_at' => now()->subDays(10)]);

    presenza($this->pg, $this->owner, GameSession::factory()->playedOn(now()->subDays(20))->create()); 
    presenza($this->pg, $this->owner, GameSession::factory()->playedOn(now()->subDays(2))->create()); 

    expect($this->pg->sessionsSinceLastLevelUp())->toBe(1)
        ->and($this->pg->canRequestLevelUp())->toBeTrue();
});

it('non conta le serate ancora da giocare', function () {
    presenza($this->pg, $this->owner, GameSession::factory()->upcoming()->create());

    expect($this->pg->sessionsSinceLastLevelUp())->toBe(0)
        ->and($this->pg->canRequestLevelUp())->toBeFalse();
});

it('senza sessioni giocate non si può chiedere', function () {
    expect($this->pg->sessionsSinceLastLevelUp())->toBe(0)
        ->and($this->pg->canRequestLevelUp())->toBeFalse();
});

describe('la legenda sulla pagina del level-up', function () {
    it('spiega la regola', function () {
        $this->actingAs($this->owner)
            ->get(route('proposals.level-up', $this->pg))
            ->assertOk()
            ->assertSee('Come si sale di livello')
            ->assertSee('ti dà diritto a un livello');
    });

    it('dice che può chiedere, quando ha giocato', function () {
        presenza($this->pg, $this->owner, GameSession::factory()->playedOn(now()->subDays(2))->create());

        $this->actingAs($this->owner)
            ->get(route('proposals.level-up', $this->pg))
            ->assertOk()
            ->assertSee('puoi chiedere il passaggio');
    });

    it('dice di giocare prima, quando non ha ancora giocato', function () {
        $this->actingAs($this->owner)
            ->get(route('proposals.level-up', $this->pg))
            ->assertOk()
            ->assertSee('di norma ne serve almeno una');
    });
});
