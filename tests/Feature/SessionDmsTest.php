<?php

declare(strict_types=1);

use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->titolare = User::factory()->dm()->create(['name' => 'Aurelio']);
    $this->comaster = User::factory()->dm()->create(['name' => 'Morgana']);
    $this->campagna = Campaign::factory()->runBy($this->titolare)->create();
});

it('dal pannello si segnano gli altri DM della sessione', function () {
    $this->actingAs($this->titolare);

    Livewire::test(CreateGameSession::class)
        ->fillForm([
            'campaign_id' => $this->campagna->id,
            'played_at' => now()->addWeek(),
            'dms' => [$this->comaster->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GameSession::first()->dms->pluck('id')->all())->toBe([$this->comaster->id]);
});

it('un co-master modifica la sessione, non si prenota e non è un sostituto', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->campagna)->create();
    $sessione->dms()->attach($this->comaster);

    expect($this->comaster->can('update', $sessione))->toBeTrue()
        ->and($this->comaster->can('book', $sessione))->toBeFalse()
        ->and($sessione->isSubstitute($this->comaster))->toBeFalse()
        ->and(User::factory()->dm()->create()->can('update', $sessione))->toBeFalse();
});

it('la pagina dice chi conduce, titolare compreso', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->campagna)->create();
    $sessione->dms()->attach($this->comaster);

    $this->actingAs(User::factory()->player()->create())
        ->get(route('sessions.show', $sessione))
        ->assertOk()
        ->assertSee('Conducono Aurelio e Morgana');
});
