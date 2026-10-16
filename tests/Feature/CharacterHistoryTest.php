<?php

declare(strict_types=1);

use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RetireCharacter;
use App\Actions\Characters\TransferSessions;
use App\Actions\Sessions\RecordAttendance;
use App\Domain\Dnd\Coins;
use App\Enums\PendingChangeStatus;
use App\Enums\SeatStatus;
use App\Livewire\DmTools;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\SessionJournal;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Anna']);
    $this->dm = User::factory()->dm()->create();

    $this->sessione = GameSession::factory()->create(['title' => 'La torre', 'played_at' => now()->subWeek()]);
    app(RecordAttendance::class)->handle($this->sessione, [$this->giocatore->id => $this->pg->id]);
});

describe('le sessioni giocate nel Registro', function () {
    it('il giocatore le vede e scrive una nota per sessione', function () {
        $this->actingAs($this->giocatore)
            ->get(route('characters.ledger', $this->pg))
            ->assertOk()
            ->assertSee('Sessioni giocate')
            ->assertSee('La torre')
            ->assertSee('+ Scrivi una nota');

        $this->actingAs($this->giocatore)
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => 'Il mago mentiva.'])
            ->assertSessionHas('status', 'Nota salvata.');

        expect($this->pg->journals()->value('body'))->toBe('Il mago mentiva.');
    });

    it('i DM leggono la nota ma non la scrivono; gli altri giocatori non la vedono', function () {
        SessionJournal::create(['character_id' => $this->pg->id, 'game_session_id' => $this->sessione->id, 'body' => 'Il mago mentiva.']);

        $this->actingAs($this->dm)->get(route('characters.ledger', $this->pg))
            ->assertSee('Il mago mentiva.')
            ->assertDontSee('+ Scrivi una nota');

        $this->actingAs($this->dm)
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => 'No'])
            ->assertForbidden();

        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.ledger', $this->pg))
            ->assertForbidden();
    });

    it('niente nota su una sessione che il personaggio non ha giocato; vuota si toglie', function () {
        $altra = GameSession::factory()->create(['played_at' => now()->subDay()]);

        $this->actingAs($this->giocatore)
            ->put(route('characters.journal', [$this->pg, $altra]), ['body' => 'No'])
            ->assertNotFound();

        SessionJournal::create(['character_id' => $this->pg->id, 'game_session_id' => $this->sessione->id, 'body' => 'Vecchia']);

        $this->actingAs($this->giocatore)
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => ''])
            ->assertSessionHas('status', 'Nota tolta.');

        expect($this->pg->journals()->count())->toBe(0);
    });

    it('gli a capo contano uno, come nel contatore della pagina', function () {
        // Il browser manda «\r\n»: 1.800 righe da un carattere sono 3.599 caratteri, non 5.398.
        $nota = implode("\r\n", array_fill(0, 1800, 'a'));

        $this->actingAs($this->giocatore)
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => $nota])
            ->assertSessionHasNoErrors();

        expect(mb_strlen($this->pg->journals()->value('body')))->toBe(3599);
    });

    it('una nota troppo lunga riapre il suo modale con l\'errore', function () {
        $this->actingAs($this->giocatore)
            ->from(route('characters.ledger', $this->pg))
            ->followingRedirects()
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => str_repeat('a', 3601)])
            ->assertSee('data-apri-subito', false)
            ->assertSee('Al massimo 3600 caratteri.');
    });

    it('dalla pagina della sessione: il diario c\'è solo se l\'hai giocata', function () {
        $nonGiocata = GameSession::factory()->create(['played_at' => now()->subDay()]);

        $this->actingAs($this->giocatore)->get(route('sessions.show', $this->sessione))
            ->assertSee('Il tuo diario')
            ->assertSee('Scrivi nel diario');

        $this->actingAs($this->giocatore)->get(route('sessions.show', $nonGiocata))
            ->assertDontSee('Il tuo diario');

        $this->actingAs($this->giocatore)
            ->from(route('sessions.show', $this->sessione))
            ->put(route('characters.journal', [$this->pg, $this->sessione]), ['body' => 'Dalla sessione', 'ancora' => 'diario'])
            ->assertRedirect(route('sessions.show', $this->sessione).'#diario');

        expect($this->pg->journals()->value('body'))->toBe('Dalla sessione');
    });

    it('si filtrano per campagna, solo fra quelle giocate', function () {
        $altra = GameSession::factory()->create(['title' => 'Il porto', 'played_at' => now()->subDays(3)]);
        $altra->attendees()->attach($this->giocatore->id, ['character_id' => $this->pg->id]);
        $mai = Campaign::factory()->create(['title' => 'Mai giocata']);

        $this->actingAs($this->giocatore)->get(route('characters.ledger', $this->pg))
            ->assertSee('Tutte le campagne')
            ->assertSee($this->sessione->campaign->title)
            ->assertSee($altra->campaign->title)
            ->assertDontSee('Mai giocata');

        $this->actingAs($this->giocatore)->get(route('characters.ledger', [$this->pg, 'campagna' => $altra->campaign->slug]))
            ->assertSee('Il porto')
            ->assertDontSee('La torre');
    });

    it('vanno a pagine da sei, numerate', function () {
        GameSession::factory()->count(6)->create(['played_at' => now()->subMonth()])
            ->each(fn ($s) => $s->attendees()->attach($this->giocatore->id, ['character_id' => $this->pg->id]));

        $this->actingAs($this->giocatore)
            ->get(route('characters.ledger', $this->pg))
            ->assertSee('7 sessioni')
            ->assertSee('Pagina 2')
            ->assertSee('Più vecchie');
    });
});

describe('il ritiro del personaggio', function () {
    it('sparisce dall\'app ma resta nel database, e le sue richieste si rifiutano', function () {
        $richiesta = app(ProposeChange::class)->loot($this->pg, $this->giocatore, coins: new Coins(gp: 10));

        app(RetireCharacter::class)->handle($this->pg, $this->dm);

        expect(Character::find($this->pg->id))->toBeNull()
            ->and(Character::withTrashed()->find($this->pg->id)->retired_by)->toBe($this->dm->id)
            ->and($richiesta->fresh()->status)->toBe(PendingChangeStatus::Rejected);

        $this->actingAs($this->dm)->get(route('characters.show', $this->pg))->assertNotFound();
        // Lo storico continua a vederlo.
        expect($richiesta->fresh()->character->name)->toBe('Anna');
    });

    it('non si ritira con un posto in una sessione futura', function () {
        $futura = GameSession::factory()->upcoming()->create();
        SessionBooking::forceCreate([
            'game_session_id' => $futura->id, 'user_id' => $this->giocatore->id,
            'character_id' => $this->pg->id, 'status' => SeatStatus::Confirmed->value,
        ]);

        expect(fn () => app(RetireCharacter::class)->handle($this->pg, $this->dm))
            ->toThrow(RuntimeException::class, 'un posto in una sessione futura');
    });

    it('lo fa un DM dalla scheda; un giocatore no', function () {
        Livewire::actingAs($this->giocatore)
            ->test(DmTools::class, ['character' => $this->pg])
            ->call('apriRitiro')
            ->assertForbidden();

        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->call('apriRitiro')
            ->call('ritira')
            ->assertHasErrors('ritiroCapito')
            ->set('ritiroCapito', true)
            ->call('ritira')
            ->assertRedirect(route('characters.index'));

        expect($this->pg->fresh()?->trashed() ?? true)->toBeTrue();
    });
});

describe('il passaggio delle sessioni', function () {
    it('presenze e diario passano al nuovo personaggio dello stesso giocatore', function () {
        SessionJournal::create(['character_id' => $this->pg->id, 'game_session_id' => $this->sessione->id, 'body' => 'Ricordo']);
        app(RetireCharacter::class)->handle($this->pg, $this->dm);
        $nuovo = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Bea']);

        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $nuovo])
            ->assertSee('Sessioni da un ritirato')
            ->call('apriPassaggio')
            ->set('passaggioDa', $this->pg->id)
            ->call('passaSessioni')
            ->assertRedirect(route('characters.show', $nuovo));

        expect($nuovo->sessions()->pluck('game_sessions.id')->all())->toBe([$this->sessione->id])
            ->and($nuovo->journals()->value('body'))->toBe('Ricordo');
    });

    it('non passano a un personaggio di un altro giocatore', function () {
        app(RetireCharacter::class)->handle($this->pg, $this->dm);
        $estraneo = Character::factory()->create();

        expect(fn () => app(TransferSessions::class)->handle(Character::withTrashed()->find($this->pg->id), $estraneo, $this->dm))
            ->toThrow(RuntimeException::class);
    });
});
