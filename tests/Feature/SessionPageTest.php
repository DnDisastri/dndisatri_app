<?php

declare(strict_types=1);

use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\User;


beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->dm = User::factory()->dm()->create();
    $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->getKey()]);
});

it('racconta la serata', function () {
    $serata = GameSession::factory()->for($this->campagna)->create([
        'number' => 12,
        'title' => 'La Torre Nera',
        'played_at' => now()->subWeek(),
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee('Sessione 12')
        ->assertSee('La Torre Nera')
        ->assertSee($this->campagna->title);
});

it('mostra il resoconto e chi l\'ha scritto', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    app(WriteRecap::class)->handle($serata, $this->dm, 'La torre è crollata, e noi eravamo dentro.');

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata->fresh()))
        ->assertOk()
        ->assertSee('La torre è crollata, e noi eravamo dentro.')
        ->assertSee($this->dm->name);
});

it('dice quando il resoconto non c\'è ancora', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee('Il resoconto non è ancora stato scritto.');
});


it('su una serata da giocare non parla di presenze né di resoconto mancante', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->addWeek()]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee('Da giocare')
        ->assertDontSee('Chi c\'era', false)
        ->assertDontSee('Il resoconto non è ancora stato scritto.');
});

it('mostra chi c\'era, col personaggio quando c\'è', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $personaggio = Character::factory()->for($this->giocatore)->create(['name' => 'Grimm']);

    app(RecordAttendance::class)->handle($serata, [$this->giocatore->getKey() => $personaggio->getKey()]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee($this->giocatore->name)
        ->assertSee('Grimm');
});


it('lascia scrivere il resoconto a chi conduce, e lo firma', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);

    $this->actingAs($this->dm)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee('Scrivi il resoconto');

    $this->actingAs($this->dm)
        ->post(route('sessions.recap', $serata), ['recap' => 'Il drago dormiva. Non più.'])
        ->assertRedirect();

    $fresca = $serata->fresh();

    expect($fresca->recap)->toBe('Il drago dormiva. Non più.')
        ->and($fresca->recap_written_by)->toBe($this->dm->getKey())
        ->and($fresca->recap_written_at)->not->toBeNull();
});

it('lascia correggere un resoconto già scritto', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    app(WriteRecap::class)->handle($serata, $this->dm, 'Prima versione.');

    $this->actingAs($this->dm)
        ->get(route('sessions.show', $serata->fresh()))
        ->assertOk()
        ->assertSee('Correggi il resoconto');

    $this->actingAs($this->dm)
        ->post(route('sessions.recap', $serata), ['recap' => 'Seconda versione.']);

    expect($serata->fresh()->recap)->toBe('Seconda versione.');
});

it('segna le presenze, e le sostituisce invece di sommarle', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $altro = User::factory()->player()->create();

    $this->actingAs($this->dm)
        ->post(route('sessions.attendance', $serata), [
            'presenti' => [$this->giocatore->getKey(), $altro->getKey()],
        ])
        ->assertRedirect();

    expect($serata->fresh()->attendees)->toHaveCount(2);

    $this->actingAs($this->dm)
        ->post(route('sessions.attendance', $serata), [
            'presenti' => [$this->giocatore->getKey()],
        ]);

    expect($serata->fresh()->attendees)->toHaveCount(1);
});

it('segna anche con quale personaggio', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $personaggio = Character::factory()->for($this->giocatore)->create();

    $this->actingAs($this->dm)->post(route('sessions.attendance', $serata), [
        'presenti' => [$this->giocatore->getKey()],
        'personaggi' => [$this->giocatore->getKey() => $personaggio->getKey()],
    ]);

    expect($serata->fresh()->attendees->first()->pivot->character_id)->toBe($personaggio->getKey());
});

// Il select può restare valorizzato nel form: il personaggio conta solo se il giocatore è stato segnato presente.
it('ignora il personaggio di chi non è stato spuntato', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $assente = User::factory()->player()->create();
    $suo = Character::factory()->for($assente)->create();

    $this->actingAs($this->dm)->post(route('sessions.attendance', $serata), [
        'presenti' => [$this->giocatore->getKey()],
        'personaggi' => [$assente->getKey() => $suo->getKey()],
    ]);

    expect($serata->fresh()->attendees)->toHaveCount(1)
        ->and($serata->fresh()->attendees->first()->getKey())->toBe($this->giocatore->getKey());
});

// I valori del select sono input utente e vengono quindi rivalidati lato server.
it('non lascia attribuire a un giocatore il personaggio di un altro', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $altrui = Character::factory()->for(User::factory()->player()->create())->create();

    $this->actingAs($this->dm)
        ->post(route('sessions.attendance', $serata), [
            'presenti' => [$this->giocatore->getKey()],
            'personaggi' => [$this->giocatore->getKey() => $altrui->getKey()],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($serata->fresh()->attendees)->toBeEmpty();
});


it('a un giocatore non dà i comandi di chi conduce', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);

    $this->actingAs($this->giocatore)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertDontSee('Conduci tu');

    $this->actingAs($this->giocatore)
        ->post(route('sessions.recap', $serata), ['recap' => 'Non dovrei potere.'])
        ->assertForbidden();

    $this->actingAs($this->giocatore)
        ->post(route('sessions.attendance', $serata), ['presenti' => [$this->giocatore->getKey()]])
        ->assertForbidden();
});

it('non dà i comandi al dungeon master di un altro tavolo', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $estraneo = User::factory()->dm()->create();

    $this->actingAs($estraneo)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertDontSee('Conduci tu');

    $this->actingAs($estraneo)
        ->post(route('sessions.recap', $serata), ['recap' => 'Nemmeno io.'])
        ->assertForbidden();
});


it('non offre gli admin fra i presenti', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subWeek()]);
    $admin = User::factory()->admin()->create(['name' => 'Un Amministratore']);

    $this->actingAs($this->dm)
        ->get(route('sessions.show', $serata))
        ->assertOk()
        ->assertSee($this->giocatore->name)
        ->assertDontSee('Un Amministratore');
});


it('dalla campagna e dalla Home si arriva alla serata', function () {
    $serata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->addWeek()]);

    $this->actingAs($this->giocatore)
        ->get(route('campaigns.show', $this->campagna))
        ->assertOk()
        ->assertSee(route('sessions.show', $serata));

    $this->actingAs($this->giocatore)
        ->get('/')
        ->assertOk()
        ->assertSee(route('sessions.show', $serata));
});


describe('il calendario', function () {
    it('mostra il mese corrente e le sue serate', function () {
        $serata = GameSession::factory()->for($this->campagna)->create([
            'played_at' => now()->startOfMonth()->addDays(9)->setTime(21, 0),
        ]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee(now()->translatedFormat('F Y'), false)
            ->assertSee($this->campagna->title)
            ->assertSee(route('sessions.show', $serata));
    });

    it('si sposta di mese dall\'indirizzo', function () {
        $altroMese = now()->startOfMonth()->addMonths(2);

        $lontana = GameSession::factory()->for($this->campagna)->create([
            'played_at' => $altroMese->copy()->addDays(4)->setTime(21, 0),
            'title' => 'La Serata Lontana',
        ]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.index', ['mese' => $altroMese->format('Y-m')]))
            ->assertOk()
            ->assertSee('La Serata Lontana')
            ->assertSee($altroMese->translatedFormat('F Y'), false);
    });

    it('su un mese senza senso ricade su quello corrente', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.index', ['mese' => 'non-un-mese']))
            ->assertOk()
            ->assertSee(now()->translatedFormat('F Y'), false);
    });

    it('lo dice quando in un mese non si gioca', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Nessuna serata in questo mese.');
    });

// Due serate nello stesso giorno non devono generare due elementi con lo stesso `id` HTML.
    it('non ripete l\'ancora quando due tavoli girano la stessa sera', function () {
        $sera = now()->startOfMonth()->addDays(14)->setTime(21, 0);
        $altra = Campaign::factory()->create();

        GameSession::factory()->for($this->campagna)->create(['played_at' => $sera]);
        GameSession::factory()->for($altra)->create(['played_at' => $sera]);

        $html = $this->actingAs($this->giocatore)->get(route('sessions.index'))->assertOk()->getContent();

        expect(substr_count($html, 'id="g-'.$sera->toDateString().'"'))->toBe(1);
    });

    it('mostra i prossimi eventi anche guardando un altro mese', function () {
        Event::factory()->create([
            'title' => 'Il Raduno di Primavera',
            'starts_at' => now()->addWeek(),
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.index', ['mese' => now()->addMonths(3)->format('Y-m')]))
            ->assertOk()
            ->assertSee('La bacheca del bardo')
            ->assertSee('Il Raduno di Primavera');
    });

    it('non mostra la bacheca quando non ci sono eventi', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertDontSee('La bacheca del bardo');
    });


    it('segna quali serate sono ancora da giocare', function () {
        GameSession::factory()->for($this->campagna)->create([
            'played_at' => now()->startOfMonth()->addDays(2),
        ]);

        $primaDelMese = now()->startOfMonth()->subMonth();

        $this->actingAs($this->giocatore)
            ->get(route('sessions.index', ['mese' => $primaDelMese->format('Y-m')]))
            ->assertOk()
            ->assertDontSee('Da giocare');

        GameSession::factory()->for($this->campagna)->create([
            'played_at' => now()->addDays(3)->setTime(21, 0),
        ]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.index', ['mese' => now()->addDays(3)->format('Y-m')]))
            ->assertOk()
            ->assertSee('Da giocare');
    });

    it('dalla Home si arriva al calendario', function () {
        $this->actingAs($this->giocatore)
            ->get('/')
            ->assertOk()
            ->assertSee(route('sessions.index'));
    });
});


describe('la serata prima e quella dopo', function () {
    beforeEach(function () {
        $this->prima = GameSession::factory()->for($this->campagna)
            ->create(['number' => 1, 'title' => 'Il pozzo', 'played_at' => now()->subWeeks(3)]);
        $this->mezzo = GameSession::factory()->for($this->campagna)
            ->create(['number' => 2, 'title' => 'La torre', 'played_at' => now()->subWeeks(2)]);
        $this->ultima = GameSession::factory()->for($this->campagna)
            ->create(['number' => 3, 'title' => 'Il ritorno', 'played_at' => now()->subWeek()]);
    });

    it('da quella di mezzo porta a entrambe', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->mezzo))
            ->assertOk()
            ->assertSee('Precedente')
            ->assertSee('Sessione 1')
            ->assertSee('Il pozzo')
            ->assertSee('Prossima')
            ->assertSee('Sessione 3')
            ->assertSee('Il ritorno')
            ->assertSee(route('sessions.show', $this->prima), false)
            ->assertSee(route('sessions.show', $this->ultima), false);
    });

    it('dalla prima non c\'è una precedente', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->prima))
            ->assertOk()
            ->assertDontSee('Precedente')
            ->assertSee('Prossima')
            ->assertSee('Sessione 2')
            ->assertSee('La torre');
    });

    it('dall\'ultima non c\'è una prossima', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->ultima))
            ->assertOk()
            ->assertSee('Precedente')
            ->assertSee('Sessione 2')
            ->assertSee('La torre')
            ->assertDontSee('Prossima');
    });

    it('e le serate di un\'altra campagna non c\'entrano', function () {
        $altra = Campaign::factory()->create();
        GameSession::factory()->for($altra)
            ->create(['number' => 9, 'title' => 'Estranea', 'played_at' => now()->subWeeks(2)]);
        $sola = Campaign::factory()->create();
        $unica = GameSession::factory()->for($sola)
            ->create(['number' => 1, 'title' => 'Unica', 'played_at' => now()->subWeek()]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $unica))
            ->assertOk()
            ->assertDontSee('Precedente')
            ->assertDontSee('Prossima')
            ->assertDontSee('Estranea');
    });
});

// L'origine viaggia in `?da=` così il ritorno e la navigazione tra serate mantengono il contesto di partenza.
describe('la freccia indietro', function () {
    beforeEach(function () {
        $this->serata = GameSession::factory()->for($this->campagna)
            ->create(['number' => 5, 'played_at' => now()->subWeek()]);
    });

    it('senza origine riporta alla campagna, e la campagna non si ripete', function () {
        $risposta = $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->serata))
            ->assertOk()
            ->assertSee('Torna a '.$this->campagna->title)
            ->assertSee(route('campaigns.show', $this->campagna), false);

        expect(substr_count($risposta->getContent(), $this->campagna->title))->toBe(1);
    });

    it('dal Libro Mastro riporta al Libro Mastro, con la campagna a contesto', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', ['session' => $this->serata, 'da' => 'libro-mastro']))
            ->assertOk()
            ->assertSee('Torna al Libro Mastro')
            ->assertSee(route('ledger.index'), false)
            ->assertSee($this->campagna->title)
            ->assertDontSee('Torna a '.$this->campagna->title);
    });

    it('dalle serate riporta alle serate', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', ['session' => $this->serata, 'da' => 'serate']))
            ->assertOk()
            ->assertSee('Torna alle serate')
            ->assertSee(route('sessions.index'), false);
    });

    it('e le serate vicine si portano dietro l\'origine', function () {
        $vicina = GameSession::factory()->for($this->campagna)
            ->create(['number' => 6, 'played_at' => now()->subDays(2)]);

        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', ['session' => $this->serata, 'da' => 'libro-mastro']))
            ->assertOk()
            ->assertSee(route('sessions.show', ['session' => $vicina, 'da' => 'libro-mastro']), false);
    });
});
