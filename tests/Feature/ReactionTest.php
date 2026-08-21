<?php

declare(strict_types=1);

use App\Actions\Sessions\WriteRecap;
use App\Enums\Reaction;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\Post;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->dm = User::factory()->dm()->create();
    $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->getKey()]);
});

function serataRaccontata(Campaign $campagna, User $dm): GameSession
{
    $serata = GameSession::factory()->for($campagna)->create(['played_at' => now()->subWeek()]);
    app(WriteRecap::class)->handle($serata, $dm, 'Il drago dormiva. Non più.');

    return $serata->fresh();
}

it('mette una reaction e la conta', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Fire->value])
        ->assertRedirect();

    expect($serata->reactions()->count())->toBe(1)
        ->and($serata->reactionOf($this->giocatore))->toBe(Reaction::Fire)
        ->and($serata->reactionCounts()['fire'])->toBe(1);
});
// Ogni utente ha una sola reaction per contenuto: sceglierne un'altra sostituisce la precedente.
it('sostituisce la reaction di prima invece di sommarla', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Fire->value]);

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Heart->value]);

    expect($serata->reactions()->count())->toBe(1)
        ->and($serata->reactionOf($this->giocatore))->toBe(Reaction::Heart);
});

it('toccando due volte la stessa la toglie', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    foreach ([1, 2] as $volta) {
        $this->actingAs($this->giocatore)
            ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Clap->value]);
    }

    expect($serata->reactions()->count())->toBe(0)
        ->and($serata->reactionOf($this->giocatore))->toBeNull();
});

it('somma le persone diverse', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);
    $altro = User::factory()->player()->create();

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Dice->value]);
    $this->actingAs($altro)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Dice->value]);

    expect($serata->reactionCounts()['dice'])->toBe(2);
});

it('sulla serata compare col resoconto, e non prima', function () {
    $raccontata = serataRaccontata($this->campagna, $this->dm);
    $daGiocare = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->addWeek()]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $raccontata))
        ->assertOk()
        ->assertSee('Applausi');

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $daGiocare))
        ->assertOk()
        ->assertDontSee('Applausi');
});

it('sulla quest compare solo da conclusa', function () {
    $aperta = Quest::factory()->inCampaign($this->campagna)->create();
    $conclusa = Quest::factory()->inCampaign($this->campagna)->completed()->create();

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $aperta))
        ->assertOk()
        ->assertDontSee('Applausi');

    $this->actingAs($this->giocatore)
        ->get(route('quests.show', $conclusa))
        ->assertOk()
        ->assertSee('Applausi');
});

it('sulla news e sull\'evento compaiono', function () {
    $post = Post::factory()->create();
    $evento = Event::factory()->create();

    $this->actingAs($this->giocatore)->get(route('news.show', $post))->assertOk()->assertSee('Applausi');
    $this->actingAs($this->giocatore)->get(route('events.show', $evento))->assertOk()->assertSee('Applausi');
});

it('su una bozza non le fa vedere nemmeno all\'admin', function () {
    $bozza = Post::factory()->draft()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('news.show', $bozza))
        ->assertOk()
        ->assertDontSee('Applausi');
});
// Una reaction non deve rendere osservabile un contenuto che l'utente non è autorizzato a vedere.
it('non lascia reagire a quello che non si può vedere', function () {
    $bozza = Post::factory()->draft()->create();

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['news', $bozza->getKey()]), ['reazione' => Reaction::Up->value])
        ->assertNotFound();

    expect($bozza->reactions()->count())->toBe(0);
});

it('non lascia reagire a quello che non è ancora finito', function () {
    $bozza = Post::factory()->draft()->create();
    $daGiocare = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->addWeek()]);
    $aperta = Quest::factory()->inCampaign($this->campagna)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('reactions.store', ['news', $bozza->getKey()]), ['reazione' => Reaction::Up->value])
        ->assertNotFound();

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $daGiocare->getKey()]), ['reazione' => Reaction::Up->value])
        ->assertNotFound();

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['incarico', $aperta->getKey()]), ['reazione' => Reaction::Up->value])
        ->assertNotFound();
});

it('su un tipo che non accetta reaction risponde 404', function () {
    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['personaggio', 1]), ['reazione' => Reaction::Up->value])
        ->assertNotFound();
});

it('rifiuta una faccina che non esiste', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => 'palle-di-fuoco'])
        ->assertSessionHasErrors('reazione');

    expect($serata->reactions()->count())->toBe(0);
});
// `aria-pressed` comunica alle tecnologie assistive quale reaction appartiene all'utente corrente.
it('segna come premuta la propria reaction', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    $this->actingAs($this->giocatore)
        ->post(route('reactions.store', ['serata', $serata->getKey()]), ['reazione' => Reaction::Heart->value]);

    $html = $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))->assertOk()->getContent();
// Il regex tollera attributi su più righe e non dipende dalla formattazione prodotta da Blade.
    expect($html)->toMatch('/value="heart"[^>]*aria-pressed="true"/')
        ->and($html)->toMatch('/value="fire"[^>]*aria-pressed="false"/');
});

it('non scrive zero sotto le faccine che nessuno ha messo', function () {
    $serata = serataRaccontata($this->campagna, $this->dm);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertDontSee('>0<', false);
});
