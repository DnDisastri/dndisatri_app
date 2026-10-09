<?php

declare(strict_types=1);

use App\Actions\Quests\ScheduleQuest;
use App\Enums\QuestDifficulty;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->dm = User::factory()->dm()->create();
    $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->getKey(), 'title' => 'Le Rovine']);
});

it('mescola gli incarichi aperti di tutte le campagne', function () {
    $altra = Campaign::factory()->create(['title' => 'La Rotta del Sale']);

    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Scortare la carovana']);
    Quest::factory()->inCampaign($altra)->create(['title' => 'Il carico sbagliato']);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Scortare la carovana')
        ->assertSee('Il carico sbagliato')

        ->assertSee('La Rotta del Sale');
});

it('non mostra gli incarichi conclusi', function () {
    Quest::factory()->inCampaign($this->campagna)->completed()->create(['title' => 'Roba Finita']);
    Quest::factory()->inCampaign($this->campagna)->closed()->create(['title' => 'Roba Abbandonata']);
    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Roba Aperta']);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Roba Aperta')
        ->assertDontSee('Roba Finita')
        ->assertDontSee('Roba Abbandonata');
});

it('filtra per campagna', function () {
    $altra = Campaign::factory()->create(['title' => 'La Rotta del Sale']);

    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Scortare la carovana']);
    Quest::factory()->inCampaign($altra)->create(['title' => 'Il carico sbagliato']);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index', ['campagna' => $altra->slug]))
        ->assertOk()
        ->assertSee('Il carico sbagliato')
        ->assertDontSee('Scortare la carovana');
});

it('filtra per difficoltà', function () {
    Quest::factory()->inCampaign($this->campagna)->create([
        'title' => 'Una Passeggiata',
        'difficulty' => QuestDifficulty::Facile,
    ]);
    Quest::factory()->inCampaign($this->campagna)->create([
        'title' => 'Il Drago Antico',
        'difficulty' => QuestDifficulty::Epica,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index', ['difficolta' => QuestDifficulty::Epica->value]))
        ->assertOk()
        ->assertSee('Il Drago Antico')
        ->assertDontSee('Una Passeggiata');
});

// Campagna e difficoltà sono filtri indipendenti e devono restare entrambi nell'URL quando combinati.
it('tiene i due filtri insieme', function () {
    $altra = Campaign::factory()->create(['title' => 'La Rotta del Sale']);

    Quest::factory()->inCampaign($this->campagna)->create([
        'title' => 'Epica Giusta',
        'difficulty' => QuestDifficulty::Epica,
    ]);
    Quest::factory()->inCampaign($this->campagna)->create([
        'title' => 'Facile Giusta',
        'difficulty' => QuestDifficulty::Facile,
    ]);
    Quest::factory()->inCampaign($altra)->create([
        'title' => 'Epica Di Un Altro Tavolo',
        'difficulty' => QuestDifficulty::Epica,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index', [
            'campagna' => $this->campagna->slug,
            'difficolta' => QuestDifficulty::Epica->value,
        ]))
        ->assertOk()
        ->assertSee('Epica Giusta')
        ->assertDontSee('Facile Giusta')
        ->assertDontSee('Epica Di Un Altro Tavolo');
});

// Filtri non validi degradano alla lista completa invece di trasformare un vecchio URL in un errore.
it('su un filtro senza senso mostra tutto', function () {
    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Scortare la carovana']);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index', ['campagna' => 'non-esiste', 'difficolta' => 'Impossibile']))
        ->assertOk()
        ->assertSee('Scortare la carovana');
});

it('mette davanti quelle già in una sessione, dalla più vicina', function () {
    Quest::factory()->inCampaign($this->campagna)->create(['title' => 'Quest Senza Data']);

    foreach (['Quest Lontana' => 20, 'Quest Vicina' => 3] as $titolo => $giorni) {
        $sessione = GameSession::factory()->inCampaign($this->campagna)->create(['played_at' => now()->addDays($giorni)]);
        app(ScheduleQuest::class)->handle(Quest::factory()->inCampaign($this->campagna)->create(['title' => $titolo]), $sessione);
    }

    $html = $this->actingAs($this->giocatore)->get(route('quests.index'))->assertOk()->getContent();

    expect(strpos($html, 'Quest Vicina'))
        ->toBeLessThan(strpos($html, 'Quest Lontana'))
        ->and(strpos($html, 'Quest Lontana'))
        ->toBeLessThan(strpos($html, 'Quest Senza Data'));
});

// Il singolare viene gestito esplicitamente perché il pluralizzatore di Laravel è orientato all'inglese.
it('dice a quanti interessa, al singolare e al plurale', function () {
    $una = Quest::factory()->inCampaign($this->campagna)->create();
    $una->interested()->attach(User::factory()->player()->create(), ['joined_at' => now()]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Interessa a 1 giocatore')
        ->assertDontSee('Interessa a 1 giocatori');
});

it('mostra se ti interessa', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();
    $quest->interested()->attach($this->giocatore, ['joined_at' => now()]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Ti interessa');
});

it('spiega perché l\'elenco è vuoto', function () {
    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Non c\'è nessuna quest aperta in questo momento.', false);

    $altra = Campaign::factory()->create();
    Quest::factory()->inCampaign($altra)->create(['difficulty' => QuestDifficulty::Facile]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index', ['difficolta' => QuestDifficulty::Epica->value, 'campagna' => $altra->slug]))
        ->assertOk()
        ->assertSee('Nessuna quest aperta con questi filtri.');
});

it('dalla Home si arriva all\'elenco', function () {
    $this->actingAs($this->giocatore)
        ->get('/')
        ->assertOk()
        ->assertSee(route('quests.index'));
});

it('nel filtro non mette le campagne senza incarichi aperti', function () {
    $vuota = Campaign::factory()->create(['title' => 'Campagna Senza Niente']);
    $altra = Campaign::factory()->create(['title' => 'La Rotta del Sale']);

    Quest::factory()->inCampaign($this->campagna)->create();
    Quest::factory()->inCampaign($altra)->create();
    Quest::factory()->inCampaign($vuota)->completed()->create();

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee(route('quests.index', ['campagna' => $altra->slug]))
        ->assertDontSee(route('quests.index', ['campagna' => $vuota->slug]));
});
