<?php

declare(strict_types=1);

use App\Actions\Quests\BookQuestSeat;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\Post;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
});

describe('la bacheca del bardo', function () {
    it('mostra i prossimi eventi e porta al loro dettaglio', function () {
        $evento = Event::factory()->create([
            'title' => 'Raduno di primavera',
            'published_at' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Raduno di primavera')
            ->assertSee(route('events.show', $evento))
            ->assertSee('Vedi tutti gli eventi');
    });
// La Home mostra solo un'anteprima degli eventi; l'elenco completo vive nella pagina dedicata.
    it('si ferma a quattro', function () {
        Event::factory()->count(6)->create([
            'published_at' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        $html = $this->actingAs($this->giocatore)->get('/')->assertOk()->getContent();

        expect(substr_count($html, 'Scopri tutti i dettagli'))->toBe(4);
    });

    it('non mostra gli eventi già passati', function () {
        Event::factory()->create([
            'title' => 'Quello dell\'anno scorso',
            'published_at' => now()->subYear(),
            'starts_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Quello dell\'anno scorso');
    });
});

describe('i prossimi tavoli', function () {
// Più campagne possono giocare nella stessa sera e devono comparire tutte.
    it('mostra tutti i tavoli della stessa sera', function () {
        $sera = now()->addWeek()->setTime(21, 0);

        $prima = Campaign::factory()->create(['title' => 'Il tavolo rosso']);
        $seconda = Campaign::factory()->create(['title' => 'Il tavolo blu']);

        GameSession::factory()->for($prima)->create(['played_at' => $sera]);
        GameSession::factory()->for($seconda)->create(['played_at' => $sera]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Il tavolo rosso')
            ->assertSee('Il tavolo blu');
    });

    it('dice chiaramente quando non c\'è niente in programma', function () {
        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Nessun tavolo in programma.');
    });
});

describe('gli incarichi aperti', function () {
    it('mostra solo quelli con ancora posto', function () {
        $campagna = Campaign::factory()->create();

        Quest::factory()->for($campagna)->create([
            'title' => 'Con posto',
            'max_participants' => 5,
        ]);

        $pieno = Quest::factory()->for($campagna)->create([
            'title' => 'Al completo',
            'max_participants' => 1,
        ]);
        app(BookQuestSeat::class)->handle($pieno, User::factory()->player()->create());

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Con posto')
            ->assertDontSee('Al completo');
    });

    it('tiene fuori quelli già conclusi', function () {
        Quest::factory()->for(Campaign::factory())->create([
            'title' => 'Finita da un pezzo',
            'completed_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Finita da un pezzo');
    });
});

it('mostra le ultime news, quelle in evidenza per prime', function () {
    Post::factory()->create([
        'title' => 'Una news normale',
        'published_at' => now()->subDay(),
        'is_pinned' => false,
    ]);

    Post::factory()->create([
        'title' => 'Una news importante',
        'published_at' => now()->subWeek(),
        'is_pinned' => true,
    ]);

    $html = $this->actingAs($this->giocatore)->get('/')->assertOk()->getContent();

    expect(strpos($html, 'Una news importante'))->toBeLessThan(strpos($html, 'Una news normale'));
});

it('non mostra un evento non ancora pubblicato, nemmeno via indirizzo diretto', function () {
    $programmato = Event::factory()->create([
        'published_at' => now()->addWeek(),
        'starts_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('events.show', $programmato))
        ->assertNotFound();
});

describe('le campagne', function () {

    it('mostra le storie aperte col solo nome, e porta dentro', function () {
        $aperta = Campaign::factory()->create(['title' => 'Le Rovine di Valcupa', 'season' => 2]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Le campagne')
            ->assertSee('Le Rovine di Valcupa')
            ->assertDontSee('Season 2')
            ->assertSee(route('campaigns.show', $aperta))
            ->assertSee(route('campaigns.index'));
    });

    it('lascia fuori le campagne concluse', function () {
        Campaign::factory()->create(['title' => 'Una Storia Finita', 'ended_at' => now()->subMonth()]);

        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Una Storia Finita');
    });

    it('lo dice invece di sparire quando non ce n\'è nessuna', function () {
        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee('Nessuna campagna aperta in questo momento.');
    });
});
// La Home limita le campagne a un'anteprima; l'elenco completo resta nella pagina Campagne.
it('sulla Home le campagne si fermano a sei', function () {
    Campaign::factory()->count(9)->create();

    $html = $this->actingAs($this->giocatore)->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'href="'.url('/campagne').'/'))->toBe(6);
});
