<?php

declare(strict_types=1);

use App\Actions\Sessions\WriteRecap;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;
use Livewire\Livewire;

it('salva il resoconto scritto dal pannello, con la firma', function () {
    $dm = User::factory()->dm()->create();
    $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);
    $session = GameSession::factory()->for($campaign)->create([
        'recap' => null,
        'recap_written_by' => null,
    ]);

    Livewire::actingAs($dm)
        ->test(EditGameSession::class, ['record' => $session->getRouteKey()])
        ->fillForm(['recap' => 'La cronaca della serata.'])
        ->call('save')
        ->assertHasNoFormErrors();

    $session->refresh();

    expect($session->recap)->toBe('La cronaca della serata.')
        ->and($session->recap_written_by)->toBe($dm->id)
        ->and($session->recap_written_at)->not->toBeNull();
});

it('non tocca la firma del resoconto se il testo non cambia', function () {
    $dm = User::factory()->dm()->create();
    $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);
    $session = GameSession::factory()->for($campaign)->create();
    app(WriteRecap::class)->handle($session, $dm, 'Testo fisso');
    $firma = $session->fresh()->recap_written_at;

    Livewire::actingAs($dm)
        ->test(EditGameSession::class, ['record' => $session->getRouteKey()])
        ->fillForm(['title' => 'Nuovo titolo'])
        ->call('save')
        ->assertHasNoFormErrors();

    $session->refresh();

    expect($session->recap)->toBe('Testo fisso')
        ->and($session->title)->toBe('Nuovo titolo')
        ->and($session->recap_written_at->equalTo($firma))->toBeTrue();
});

it('salva le presenze scelte dal pannello, col personaggio', function () {
    $dm = User::factory()->dm()->create();
    $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);
    $session = GameSession::factory()->for($campaign)->create();

    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();

    Livewire::actingAs($dm)
        ->test(EditGameSession::class, ['record' => $session->getRouteKey()])
        ->fillForm(['presenze' => [
            ['user_id' => $player->id, 'character_id' => $character->id],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $session->refresh()->load('attendees');

    expect($session->attendees)->toHaveCount(1)
        ->and($session->attendees->first()->id)->toBe($player->id)
        ->and((int) $session->attendees->first()->pivot->character_id)->toBe($character->id);
});

it('carica nel form le presenze già registrate', function () {
    $dm = User::factory()->dm()->create();
    $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);
    $session = GameSession::factory()->for($campaign)->create();

    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $session->attendees()->sync([$player->id => ['character_id' => $character->id]]);

    Livewire::actingAs($dm)
        ->test(EditGameSession::class, ['record' => $session->getRouteKey()])
        ->assertFormSet(fn (array $state) => count($state['presenze']) === 1
            && (int) collect($state['presenze'])->first()['user_id'] === $player->id);
});
