<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;

it('dà una pagina a ognuna delle cinque voci della barra', function () {
    foreach ([
        'campaigns.index',
        'ledger.index',
        'characters.index',
        'market.index',
        'events.index',
    ] as $rotta) {
        expect(Route::has($rotta))->toBeTrue("Manca la rotta {$rotta}");
    }
});

describe('le campagne', function () {
    it('mette le attive prima delle concluse', function () {
        $finita = Campaign::factory()->create(['title' => 'Zeta finita', 'ended_at' => now()->subMonth()]);
        $viva = Campaign::factory()->create(['title' => 'Alfa viva', 'ended_at' => null]);

        $risposta = $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.index'));

        $risposta->assertOk();

        $html = $risposta->getContent();
        expect(strpos($html, $viva->title))->toBeLessThan(strpos($html, $finita->title));
    });

    it('restringe l\'elenco alla season chiesta', function () {
        $prima = Campaign::factory()->create(['season' => 1, 'title' => 'Quella vecchia']);
        $seconda = Campaign::factory()->create(['season' => 2, 'title' => 'Quella nuova']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.index', ['season' => 2]))
            ->assertOk()
            ->assertSee($seconda->title)
            ->assertDontSee($prima->title);
    });
// Una season non valida degrada all'elenco completo, così vecchi URL non diventano errori.
    it('ricade su tutte se la season non esiste', function () {
        $campagna = Campaign::factory()->create(['season' => 1]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.index', ['season' => 99]))
            ->assertOk()
            ->assertSee($campagna->title);
    });

    it('apre il dettaglio con il capogilda e le serate', function () {
        $campagna = Campaign::factory()->create([
            'quest_giver' => 'Berengario il Grigio',
            'description' => 'Una storia di taverne.',
        ]);

        $sessione = GameSession::factory()->for($campagna)->create([
            'played_at' => now()->subWeek(),
            'recap' => 'Abbiamo perso il carro.',
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.show', $campagna))
            ->assertOk()
            ->assertSee($campagna->title)
            ->assertSee('Berengario il Grigio')
            ->assertSee('Una storia di taverne.')
            ->assertSee('Abbiamo perso il carro.');
    });
});

describe('i miei eroi', function () {
    it('a chi non ha personaggi propone di crearne uno', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.index'))
            ->assertOk()
            ->assertSee('Non hai ancora un personaggio')
            ->assertSee(route('characters.create'));
    });

    it('a chi ce l\'ha mostra la card e gli accessi', function () {
        $giocatore = User::factory()->player()->create();
        $pg = Character::factory()->ownedBy($giocatore)->create(['name' => 'Grimm']);

        $this->actingAs($giocatore)
            ->get(route('characters.index'))
            ->assertOk()
            ->assertSee('Grimm')
            ->assertSee(route('characters.show', $pg))
            ->assertSee(route('proposals.level-up', $pg))
            ->assertDontSee('Non hai ancora un personaggio');
    });

    it('e su un caduto non propone quello che non si può fare', function () {
        $giocatore = User::factory()->player()->create();
        $caduto = Character::factory()->ownedBy($giocatore)->fallen()->create(['name' => 'Povero Yorick']);

        $this->actingAs($giocatore)
            ->get(route('characters.index'))
            ->assertOk()
            ->assertSee('Povero Yorick')
            ->assertSee(route('characters.show', $caduto))
            ->assertDontSee(route('proposals.level-up', $caduto))
            ->assertDontSee(route('proposals.loot', $caduto));
    });

    it('mostra solo i propri personaggi', function () {
        $mio = User::factory()->player()->create();
        Character::factory()->ownedBy($mio)->create(['name' => 'Grimm']);
        Character::factory()->ownedBy(User::factory()->player()->create())->create(['name' => 'Estranea']);

        $this->actingAs($mio)
            ->get(route('characters.index'))
            ->assertOk()
            ->assertSee('Grimm')
            ->assertDontSee('Estranea');
    });

    it('lascia il riquadro delle richieste anche quando è vuoto', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.index'))
            ->assertOk()
            ->assertSee('Non hai ancora effettuato richieste');
    });
});

describe('gli eventi', function () {
    it('mostra i pubblicati e nasconde i programmati', function () {
        $visibile = Event::factory()->create([
            'title' => 'Raduno di primavera',
            'published_at' => now()->subDay(),
            'starts_at' => now()->addWeek(),
        ]);

        $programmato = Event::factory()->create([
            'title' => 'La sorpresa',
            'published_at' => now()->addWeek(),
            'starts_at' => now()->addMonth(),
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee($visibile->title)
            ->assertDontSee($programmato->title);
    });

    it('mette i prossimi prima dei passati', function () {
        Event::factory()->create([
            'title' => 'Quello vecchio',
            'published_at' => now()->subMonth(),
            'starts_at' => now()->subWeek(),
        ]);

        Event::factory()->create([
            'title' => 'Quello nuovo',
            'published_at' => now()->subMonth(),
            'starts_at' => now()->addWeek(),
        ]);

        $html = $this->actingAs(User::factory()->player()->create())
            ->get(route('events.index'))
            ->assertOk()
            ->getContent();

        expect(strpos($html, 'Quello nuovo'))->toBeLessThan(strpos($html, 'Quello vecchio'));
    });
});

describe('il Libro Mastro', function () {
    it('raccoglie serate e incarichi conclusi, e le voci portano alla loro pagina', function () {
        $campagna = Campaign::factory()->create();

        $serata = GameSession::factory()->for($campagna)->create([
            'played_at' => now()->subWeek(),
            'recap' => 'La torre è crollata.',
        ]);

        $quest = Quest::factory()->for($campagna)->create([
            'title' => 'Il carico perduto',
            'completed_at' => now()->subDays(3),
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('ledger.index'))
            ->assertOk()
            ->assertSee('La torre è crollata.')
            ->assertSee('Il carico perduto')
            ->assertSee(route('sessions.show', $serata), false)
            ->assertSee(route('quests.show', $quest), false)
            ->assertSee('andata a buon fine')
            ->assertSee('abbandonata');
    });

    it('ma non i caduti: quelli stanno in Gilda', function () {
        Character::factory()->create(['name' => 'Povero Ulf', 'died_at' => now()->subDays(2)]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('ledger.index'))
            ->assertOk()
            ->assertDontSee('Povero Ulf')
            ->assertDontSee('Chi non è tornato');
    });


    it('tiene fuori gli incarichi ancora aperti', function () {
        $campagna = Campaign::factory()->create();
        Quest::factory()->for($campagna)->create(['title' => 'Ancora da fare']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('ledger.index'))
            ->assertOk()
            ->assertDontSee('Ancora da fare');
    });

    it('restringe alla campagna scelta', function () {
        $questa = Campaign::factory()->create(['slug' => 'questa', 'season' => 1]);
        $altra = Campaign::factory()->create(['slug' => 'altra', 'season' => 1]);

        Quest::factory()->for($questa)->create(['title' => 'Della prima', 'completed_at' => now()]);
        Quest::factory()->for($altra)->create(['title' => 'Della seconda', 'completed_at' => now()]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('ledger.index', ['campagna' => 'questa']))
            ->assertOk()
            ->assertSee('Della prima')
            ->assertDontSee('Della seconda');
    });
});

// `backdrop-blur` crea un contesto di impilamento: l'header deve restare sopra la barra e il contenuto.
it('l\'intestazione sta sopra la barra in basso, e non sotto', function () {
    $html = $this->actingAs(User::factory()->player()->create())
        ->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($html)->toMatch('/<header class="[^"]*\brelative\b/');

    preg_match('/<header class="([^"]*)"/', $html, $intestazione);
    preg_match('/<nav class="([^"]*)" aria-label="Navigazione principale"/', $html, $barra);

    preg_match('/\bz-(\d+)\b/', $intestazione[1], $zIntestazione);
    preg_match('/\bz-(\d+)\b/', $barra[1], $zBarra);

    expect((int) ($zIntestazione[1] ?? 0))->toBeGreaterThan((int) ($zBarra[1] ?? 0));
});
