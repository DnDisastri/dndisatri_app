<?php

declare(strict_types=1);

use App\Actions\Quests\BookQuestSeat;
use App\Enums\QuestDifficulty;
use App\Models\Campaign;
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

// L'ordinamento privilegia le quest dove un nuovo partecipante può ancora contribuire al tavolo.
it('mette davanti chi ha bisogno di gente e in fondo i pieni', function () {
    $pieno = Quest::factory()->inCampaign($this->campagna)->slots(1)->create([
        'title' => 'Incarico Pieno',
        'min_participants' => 1,
    ]);
    app(BookQuestSeat::class)->handle($pieno, User::factory()->player()->create());

    Quest::factory()->inCampaign($this->campagna)->slots(5)->create([
        'title' => 'Incarico Pronto',
        'min_participants' => 0,
    ]);

    Quest::factory()->inCampaign($this->campagna)->slots(5)->create([
        'title' => 'Incarico Che Cerca Gente',
        'min_participants' => 3,
    ]);

    $html = $this->actingAs($this->giocatore)->get(route('quests.index'))->assertOk()->getContent();

    expect(strpos($html, 'Incarico Che Cerca Gente'))
        ->toBeLessThan(strpos($html, 'Incarico Pronto'))
        ->and(strpos($html, 'Incarico Pronto'))
        ->toBeLessThan(strpos($html, 'Incarico Pieno'));
});

it('dice quanti ne mancano perché la serata parta', function () {
    Quest::factory()->inCampaign($this->campagna)->slots(5)->create([
        'title' => 'Scortare la carovana',
        'min_participants' => 3,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Mancano 3 giocatori');
});

// Il singolare viene gestito esplicitamente perché il pluralizzatore di Laravel è orientato all'inglese.
it('al singolare cambia sia il verbo sia il nome', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(5)->create([
        'title' => 'Scortare la carovana',
        'min_participants' => 2,
    ]);
    app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Manca 1 giocatore')
        ->assertDontSee('Mancano 1');
});


it('mostra il proprio posto', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create([
        'title' => 'Scortare la carovana',
    ]);
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->giocatore)
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Hai prenotato');
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
