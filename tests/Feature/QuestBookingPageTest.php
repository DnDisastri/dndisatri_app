<?php

declare(strict_types=1);

use App\Actions\Quests\BookQuestSeat;
use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ConfirmQuestNight;
use App\Enums\QuestOutcome;
use App\Enums\QuestSeatStatus;
use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;


beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->dm = User::factory()->dm()->create();
    $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->getKey()]);
});

it('racconta l\'incarico', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create([
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

it('offre di partecipare quando c\'è posto', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Voglio partecipare')
        ->assertSee(route('quests.book', $quest));
});

it('prenota, e lo dice', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();

    $this->actingAs($this->giocatore)
        ->post(route('quests.book', $quest))
        ->assertRedirect();

    expect($quest->fresh()->seatOf($this->giocatore))->toBe(QuestSeatStatus::Booked);
});

// A posti esauriti la prenotazione resta possibile e diventa un ingresso in lista d'attesa.
it('a posti esauriti propone la lista d\'attesa invece di sparire', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(1)->create();
    app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Entro in lista d\'attesa')
        ->assertDontSee('Voglio partecipare');

    $this->actingAs($this->giocatore)->post(route('quests.book', $quest));

    expect($quest->fresh()->seatOf($this->giocatore))->toBe(QuestSeatStatus::Waiting);
});

it('a chi è già dentro offre di tirarsi indietro', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Mi tiro indietro')
        ->assertDontSee('Voglio partecipare');

    $this->actingAs($this->giocatore)
        ->post(route('quests.withdraw', $quest))
        ->assertRedirect();

    expect($quest->fresh()->seatOf($this->giocatore))->toBe(QuestSeatStatus::Withdrawn);
});

it('mostra quanti ne mancano perché la serata parta', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(6)->create([
        'min_participants' => 4,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Mancano 4 giocatori');
});
// Il singolare viene gestito esplicitamente perché il pluralizzatore di Laravel è orientato all'inglese.
it('quando ne manca uno lo dice al singolare', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(6)->create([
        'min_participants' => 1,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Manca 1 giocatore')
        ->assertDontSee('Mancano 1');
});

it('mostra chi si è prenotato, per nome', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    $altro = User::factory()->player()->create(['name' => 'Bruno il Prudente']);
    app(BookQuestSeat::class)->handle($quest, $altro);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Bruno il Prudente')
        ->assertSee('Prenotato');
});

it('mostra la lista d\'attesa in ordine di arrivo', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(1)->create();
    app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

    $primo = User::factory()->player()->create(['name' => 'Prima Arrivata']);
    $secondo = User::factory()->player()->create(['name' => 'Secondo Arrivato']);
    app(BookQuestSeat::class)->handle($quest, $primo);
    $quest->participants()->updateExistingPivot($primo, ['joined_at' => now()->subHour()]);
    app(BookQuestSeat::class)->handle($quest, $secondo);

// `false` evita l'escape dell'expected perché l'apostrofo è scritto direttamente nel template.
    $pagina = $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('In lista d\'attesa, in ordine di arrivo', false);

    expect(strpos($pagina->getContent(), 'Prima Arrivata'))
        ->toBeLessThan(strpos($pagina->getContent(), 'Secondo Arrivato'));
});

it('non lascia prenotare a un incarico già concluso', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->completed()->create();

    $this->actingAs($this->giocatore)
        ->post(route('quests.book', $quest))
        ->assertForbidden();
});


it('offre al dungeon master di confermare la serata, e la conferma', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->dm)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('La serata si fa');

    $this->actingAs($this->dm)
        ->post(route('quests.confirm-night', $quest))
        ->assertRedirect();

    expect($quest->fresh()->isNightConfirmed())->toBeTrue()
        ->and($quest->fresh()->seatOf($this->giocatore))->toBe(QuestSeatStatus::Confirmed);
});

it('avvisa il dungeon master che è sotto il minimo, senza impedirglielo', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(6)->create([
        'min_participants' => 4,
    ]);
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->dm)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Siete sotto il minimo di 4');

    $this->actingAs($this->dm)->post(route('quests.confirm-night', $quest));

    expect($quest->fresh()->isNightConfirmed())->toBeTrue();
});

it('chiama qualcuno dalla lista d\'attesa quando un posto si libera', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(1)->create();
    $dentro = User::factory()->player()->create();
    app(BookQuestSeat::class)->handle($quest, $dentro);
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->dm)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertDontSee('Fallo entrare');

    $quest->participants()->updateExistingPivot($dentro, [
        'status' => QuestSeatStatus::Withdrawn->value,
    ]);

    $this->actingAs($this->dm)
        ->get(route('quests.show', $quest->fresh()))
        ->assertOk()
        ->assertSee('Fallo entrare');

    $this->actingAs($this->dm)
        ->post(route('quests.promote', $quest), ['user_id' => $this->giocatore->getKey()])
        ->assertRedirect();

    expect($quest->fresh()->seatOf($this->giocatore))->toBe(QuestSeatStatus::Booked);
});

// Lo stato può cambiare tra caricamento della pagina e submit: il fallimento deve restare gestito.
it('spiega perché non può chiamare, invece di rompersi', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    $mai = User::factory()->player()->create();

    $this->actingAs($this->dm)
        ->post(route('quests.promote', $quest), ['user_id' => $mai->getKey()])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('conclude l\'incarico raccontando com\'è andata', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();

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
        ->assertDontSee('Voglio partecipare');
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
it('non dà i comandi al dungeon master di un altro tavolo', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $estraneo = User::factory()->dm()->create();

    $this->actingAs($estraneo)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertDontSee('Conduci tu')
        ->assertDontSee('Concludi l\'incarico', false);

    $this->actingAs($estraneo)
        ->post(route('quests.confirm-night', $quest))
        ->assertForbidden();

    $this->actingAs($estraneo)
        ->post(route('quests.conclude', $quest), ['outcome' => QuestOutcome::Closed->value])
        ->assertForbidden();
});

it('a un giocatore non mostra i comandi di chi conduce', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertDontSee('Conduci tu');
});


it('rifiuta un esito che non esiste', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs($this->dm)
        ->post(route('quests.conclude', $quest), ['outcome' => 'active'])
        ->assertSessionHasErrors('outcome');

    expect($quest->fresh()->isActive())->toBeTrue();
});


it('dalla campagna si arriva all\'incarico, e non ci si prenota più da lì', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertSee(route('quests.show', $quest))
        ->assertDontSee('Voglio partecipare');
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
        ->assertSee('Le 2 quest concluse di questo tavolo')
        ->assertSee(route('ledger.index', ['campagna' => $this->campagna->slug]));
});

it('senza quest concluse non offre nessun archivio', function () {
    Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertDontSee('quest concluse di questo tavolo');
});

it('sulla campagna si vede il proprio posto, senza aprire ogni incarico', function () {
    $quest = Quest::factory()->inCampaign($this->campagna)->slots(4)->create();
    app(BookQuestSeat::class)->handle($quest, $this->giocatore);
    app(ConfirmQuestNight::class)->handle($quest->fresh());

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertSee('La serata si fa')
        ->assertSee('Posto confermato');
});
