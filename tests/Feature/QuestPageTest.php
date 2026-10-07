<?php

declare(strict_types=1);

use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ScheduleQuest;
use App\Enums\QuestOutcome;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->dm = User::factory()->dm()->create();
    $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->getKey()]);
});

it('racconta la quest', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create([
        'title' => 'Scortare la carovana',
        'description' => 'Tre carri, una strada sola e i lupi che cantano.',
        'rewards' => '200 mo e la gratitudine del mercante',
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Scortare la carovana')
        ->assertSee('Tre carri, una strada sola')
        ->assertSee('200 mo e la gratitudine del mercante')
        ->assertSee($this->campagna->title);
});

it('offre «mi interessa» e mostra a chi interessa', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    $quest->interested()->attach(User::factory()->player()->create(['name' => 'Bruno il Prudente']), ['joined_at' => now()]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Mi interessa')
        ->assertSee('Interessa a 1 giocatore')
        ->assertSee('Bruno il Prudente')
        ->assertSee('non l\'ha ancora messa in una sessione', false);
});

it('quando è in una sessione manda a prenotarsi lì', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->campagna)->create();
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    app(ScheduleQuest::class)->handle($quest, $sessione);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Vai alla sessione e prenotati')
        ->assertSee(route('sessions.show', $sessione));
});

it('al DM della campagna offre le sessioni in programma', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->campagna)->create(['title' => 'Il guado']);
    GameSession::factory()->upcoming()->create(['title' => 'Di un\'altra campagna']);
    $quest = Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs($this->dm)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('In quale sessione si gioca')
        ->assertSee('Il guado')
        ->assertDontSee('Di un\'altra campagna', false);
});

it('conclude la quest raccontando com\'è andata', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs($this->dm)
        ->post(route('quests.conclude', $quest), [
            'outcome' => QuestOutcome::Completed->value,
            'outcome_notes' => 'La carovana è arrivata, i lupi no.',
        ])
        ->assertRedirect();

    expect($quest->fresh()->outcome())->toBe(QuestOutcome::Completed);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Com\'è andata', false)
        ->assertSee('La carovana è arrivata, i lupi no.')
        ->assertDontSee('Mi interessa');
});

it('dice che nessuno ha raccontato come è andata, quando è così', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    app(ConcludeQuest::class)->handle($quest, QuestOutcome::Closed);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('nessuno ha');
});

// I comandi della quest appartengono al DM della campagna, non a qualunque utente con ruolo DM.
it('non dà i comandi al DM di un\'altra campagna', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    $estraneo = User::factory()->dm()->create();

    $this->actingAs($estraneo)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertDontSee('In quale sessione si gioca');

    $this->actingAs($estraneo)
        ->post(route('quests.schedule', $quest), ['game_session_id' => null])
        ->assertForbidden();

    $this->actingAs($estraneo)
        ->post(route('quests.conclude', $quest), ['outcome' => QuestOutcome::Closed->value])
        ->assertForbidden();
});

it('rifiuta un esito che non esiste', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs($this->dm)
        ->post(route('quests.conclude', $quest), ['outcome' => 'active'])
        ->assertSessionHasErrors('outcome');

    expect($quest->fresh()->isActive())->toBeTrue();
});

it('sulla campagna mostra solo le quest aperte, e manda al Libro Mastro per le altre', function () {
    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Quella Aperta']);
    Quest::factory()->inCampaign($this->campagna)->completed()->create(['title' => 'Quella Finita']);
    Quest::factory()->inCampaign($this->campagna)->closed()->create(['title' => 'Quella Abbandonata']);

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertSee('Quella Aperta')
        ->assertDontSee('Quella Finita')
        ->assertDontSee('Quella Abbandonata')
        ->assertSee(route('ledger.index', ['campagna' => $this->campagna->slug]));
});

it('sulla campagna la card dice se ti interessa e quando si gioca', function () {
    $sessione = GameSession::factory()->upcoming()->inCampaign($this->campagna)->create();
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    $quest->interested()->attach($this->giocatore, ['joined_at' => now()]);
    app(ScheduleQuest::class)->handle($quest, $sessione);

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertSee('Ti interessa')
        ->assertSee('Si gioca');
});
