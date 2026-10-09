<?php

declare(strict_types=1);

use App\Actions\Characters\AdjustHitPoints;
use App\Actions\Users\IssueWarning;
use App\Enums\EncounterStatus;
use App\Enums\SeatStatus;
use App\Livewire\CombatTracker;
use App\Livewire\HitPointTracker;
use App\Livewire\NpcManager;
use App\Livewire\SessionPrep;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\Encounter;
use App\Models\GameSession;
use App\Models\Monster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    $this->dm = User::factory()->dm()->create(['name' => 'Dungeon Mario']);
    $this->campagna = Campaign::factory()->create([
        'title' => 'Le Rovine di Valcupa',
        'slug' => 'valcupa',
        'dm_id' => $this->dm->id,
    ]);

    $this->giocatore = User::factory()->player()->create();
    $this->anna = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Anna Ventochiara']);
    // Il roster della Regia deriva dai personaggi registrati nelle presenze delle sessioni già giocate.
    $giocata = GameSession::factory()->for($this->campagna)->create([
        'number' => 1, 'played_at' => now()->subWeek(),
    ]);
    $giocata->attendees()->attach($this->giocatore->id, ['character_id' => $this->anna->id]);

    $this->prossima = GameSession::factory()->for($this->campagna)->create([
        'number' => 2, 'played_at' => now()->addWeek(),
    ]);

    $this->scontro = Encounter::factory()->for($this->campagna)->create();
});

describe('la Regia (home)', function () {
    it('il DM vede la sua campagna, gli eroi confermati e la porta della sessione', function () {
        $this->scontro->update(['game_session_id' => $this->prossima->id]);

        // Ha giocato la campagna ma non ha un posto: nell'Area Master non compare.
        $this->actingAs($this->dm)
            ->get(route('dm.home'))
            ->assertOk()
            ->assertSee('Le Rovine di Valcupa')
            ->assertDontSee('Anna Ventochiara')
            ->assertSee('Gli eroi compariranno qui')
            ->assertSee('Conduci la sessione')
            ->assertSee('Imboscata sul ponte');

        $this->prossima->players()->attach($this->giocatore->id, [
            'character_id' => $this->anna->id,
            'status' => SeatStatus::Confirmed->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($this->dm)->get(route('dm.home'))->assertSee('Anna Ventochiara');
    });

    it('un giocatore non ci entra', function () {
        $this->actingAs($this->giocatore)
            ->get(route('dm.home'))
            ->assertForbidden();
    });

    it('un DM che copre un collega vede la traccia del sostituto', function () {
        $altroDm = User::factory()->dm()->create();

        $this->actingAs($altroDm)
            ->get(route('dm.home', ['campagna' => 'valcupa']))
            ->assertOk()
            ->assertSee('Stai coprendo')
            ->assertSee('Dungeon Mario');
    });
});

describe('conduci la sessione (sulla pagina della sessione, P21)', function () {
    it('il DM della campagna vede gli eroi e i comandi, senza pagine doppie', function () {
        $this->actingAs($this->dm)
            ->get(route('sessions.show', ['session' => $this->prossima, 'da' => 'regia']))
            ->assertOk()
            ->assertSee('Gli eroi della sessione')
            ->assertSee('Anna Ventochiara')
            ->assertSee('Combattimenti')
            ->assertSee('Appunti')
            ->assertSee('Chiudi la sessione');
    });

    it('un sostituto vede gli eroi e può chiudere la sessione', function () {
        $altroDm = User::factory()->dm()->create();

        $this->actingAs($altroDm)
            ->get(route('sessions.show', $this->prossima))
            ->assertOk()
            ->assertSee('Anna Ventochiara')
            ->assertSee('Chiudi la sessione');
    });

    it('mostra i combattimenti collegati, solo ai DM', function () {
        $this->scontro->update(['game_session_id' => $this->prossima->id]);

        $this->actingAs($this->dm)->get(route('sessions.show', $this->prossima))->assertSee('Imboscata sul ponte');
        $this->actingAs($this->giocatore)->get(route('sessions.show', $this->prossima))->assertDontSee('Imboscata sul ponte');
    });

    it('un giocatore non vede gli eroi da DM', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->prossima))
            ->assertOk()
            ->assertDontSee('Gli eroi della sessione');
    });
});

describe('prepara la sessione', function () {
    it('la vecchia pagina porta alla sessione, dove ora stanno gli appunti', function () {
        $this->actingAs($this->dm)
            ->get(route('dm.prepare', $this->prossima))
            ->assertRedirect(route('sessions.show', ['session' => $this->prossima, 'da' => 'regia']));

        $this->actingAs($this->dm)
            ->get('/regia')
            ->assertRedirect('/area-master');
    });

    it('un giocatore non ci entra', function () {
        $this->actingAs($this->giocatore)
            ->get(route('dm.prepare', $this->prossima))
            ->assertForbidden();
    });

    it('gli appunti si salvano sulla sessione, privati', function () {
        Livewire::actingAs($this->dm)
            ->test(SessionPrep::class, ['session' => $this->prossima])
            ->set('note', 'Il ponte crolla al terzo round.')
            ->call('salvaNote')
            ->assertHasNoErrors();

        expect($this->prossima->refresh()->dm_notes)->toBe('Il ponte crolla al terzo round.');
    });
});

describe('il tracker di combattimento', function () {
    it('un giocatore non ci entra', function () {
        Livewire::actingAs($this->giocatore)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->assertForbidden();
    });

    it('aggiunge gli eroi, a iniziativa zero', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi')
            ->assertHasNoErrors();

        $anna = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id);
        expect($anna)->not->toBeNull()
            ->and($anna['tipo'])->toBe('pg')
            ->and($anna['iniziativa'])->toBe(0);
    });

    it('aggiunge un mostro al volo con PF e CA', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->set('mostroNome', 'Goblin')->set('mostroHp', 7)->set('mostroAc', 15)
            ->call('aggiungiMostro')
            ->assertHasNoErrors();

        $mob = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin');
        expect($mob['tipo'])->toBe('mostro')
            ->and($mob['hp'])->toBe(7)
            ->and($mob['ac'])->toBe(15);
    });

    it('il danno a un eroe scende sui PF veri della scheda', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 30]);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->set("colpo.{$id}", 7)->call('danno', $id)->assertHasNoErrors();

        expect($this->anna->refresh()->hp_current)->toBe(23);
    });

    it('il danno a un mostro scende sul suo numero, non sotto zero', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->set('mostroNome', 'Goblin')->set('mostroHp', 7)->set('mostroAc', 15)
            ->call('aggiungiMostro');

        $id = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin')['id'];

        $comp->set("colpo.{$id}", 100)->call('danno', $id);

        $mob = collect($this->scontro->refresh()->combatants)->firstWhere('nome', 'Goblin');
        expect($mob['hp'])->toBe(0);
    });

    it('mette e toglie una condizione dalla lista fissa', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->call('condizione', $id, 'poisoned');
        $addosso = fn () => collect($this->scontro->refresh()->combatants)->firstWhere('id', $id)['condizioni'];
        expect($addosso())->toContain('poisoned');

        $comp->call('condizione', $id, 'poisoned');
        expect($addosso())->not->toContain('poisoned');
    });

    it('il DM segna un tiro morte a un eroe a terra', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0]);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->call('tiroMorte', $id, 'fallimento', 2);

        expect($this->anna->refresh()->death_save_failures)->toBe(2);
    });

    it('il giocatore segna i tiri morte sulla sua scheda: lo stesso dato', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0]);

        Livewire::actingAs($this->giocatore)
            ->test(HitPointTracker::class, ['character' => $this->anna])
            ->call('tiroMorte', 'successo', 3);

        expect($this->anna->refresh()->death_save_successes)->toBe(3);
    });

    it('curare sopra zero azzera i tiri morte', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0, 'death_save_failures' => 2]);

        app(AdjustHitPoints::class)->heal($this->anna->refresh(), 5);

        expect($this->anna->refresh())
            ->hp_current->toBe(5)
            ->death_save_failures->toBe(0);
    });

    it('non si segnano tiri morte se non è a terra', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 30]);

        $this->anna->segnaTiroMorte('fallimento', 3);

        expect($this->anna->refresh()->death_save_failures)->toBe(0);
    });

    it('pesca un mostro dal bestiario, con lo statblock', function () {
        $goblin = Monster::factory()->create([
            'name' => 'Goblin', 'hp' => 7, 'ac' => 15, 'traits' => 'Fuga astuta.',
        ]);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiDalBestiario', $goblin->id);

        $mob = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin');
        expect($mob['tipo'])->toBe('mostro')
            ->and($mob['hp'])->toBe(7)
            ->and($mob['monsterId'])->toBe($goblin->id)
            ->and($mob['traits'])->toBe('Fuga astuta.');
    });

    it('non pesca un mostro legato a un\x27altra campagna', function () {
        $altraCampagna = Campaign::factory()->create(['dm_id' => User::factory()->dm()->create()->id]);
        $estraneo = Monster::factory()->create(['name' => 'Estraneo', 'campaign_id' => $altraCampagna->id]);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiDalBestiario', $estraneo->id);

        expect(collect($comp->get('combattenti'))->firstWhere('nome', 'Estraneo'))->toBeNull();
    });

    it('salvando al volo, il mostro entra anche nel bestiario', function () {
        Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->set('mostroNome', 'Orco')->set('mostroHp', 15)->set('mostroAc', 13)
            ->set('salvaNelBestiario', true)
            ->call('aggiungiMostro')
            ->assertHasNoErrors();

        expect(Monster::where('name', 'Orco')->exists())->toBeTrue();
    });

    it('apre lo statblock esteso al clic', function () {
        $goblin = Monster::factory()->create(['name' => 'Goblin']);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiDalBestiario', $goblin->id);

        $id = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin')['id'];

        $comp->call('apriStatblock', $id)
            ->assertSet('statblockAperto', $id)
            ->assertSee('Attacchi');
    });

    it('riordina per iniziativa quando cambia un numero in riga', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi')
            ->set('mostroNome', 'Goblin')->set('mostroHp', 7)->set('mostroAc', 15)
            ->call('aggiungiMostro');

        $combattenti = $comp->get('combattenti');
        $iAnna = collect($combattenti)->search(fn ($c) => ($c['characterId'] ?? null) === $this->anna->id);

        $comp->set("combattenti.{$iAnna}.iniziativa", 25);

        $ordine = $this->scontro->refresh()->combatants;
        expect($ordine[0]['nome'])->toBe('Anna Ventochiara')
            ->and($ordine[0]['iniziativa'])->toBe(25);
    });
});

describe('i combattimenti', function () {
    it('il DM ne crea più d\'uno, anche senza sessione', function () {
        $this->actingAs($this->dm)
            ->post(route('encounters.store'), ['campaign_id' => $this->campagna->id, 'title' => 'Goblin al guado'])
            ->assertRedirect();

        $this->actingAs($this->dm)
            ->post(route('encounters.store'), [
                'campaign_id' => $this->campagna->id, 'title' => 'Il drago', 'game_session_id' => $this->prossima->id,
            ]);

        expect($this->campagna->encounters()->count())->toBe(3)
            ->and(Encounter::where('title', 'Il drago')->value('game_session_id'))->toBe($this->prossima->id)
            ->and(Encounter::where('title', 'Goblin al guado')->value('game_session_id'))->toBeNull();
    });

    it('non si collegano alla sessione di un\'altra campagna', function () {
        $altra = GameSession::factory()->create();

        $this->actingAs($this->dm)
            ->patch(route('encounters.update', $this->scontro), ['title' => 'X', 'game_session_id' => $altra->id])
            ->assertSessionHasErrors('game_session_id');
    });

    it('li apre qualsiasi DM, mai un giocatore', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(route('encounters.show', $this->scontro))
            ->assertOk()
            ->assertSee('Aggiungi gli eroi');

        $this->actingAs($this->giocatore)->get(route('encounters.show', $this->scontro))->assertForbidden();
        $this->actingAs($this->giocatore)->get(route('encounters.index'))->assertForbidden();
    });

    it('il primo turno lo mette in corso, e si conclude', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['encounter' => $this->scontro])
            ->call('aggiungiEroi')
            ->call('prossimo');

        expect($this->scontro->refresh()->status)->toBe(EncounterStatus::Running);

        $comp->call('concludi');

        expect($this->scontro->refresh()->isEnded())->toBeTrue();
    });

    it('la migrazione trasforma l\'iniziativa delle sessioni in combattimenti', function () {
        $migrazione = require database_path('migrations/2026_10_08_100000_create_encounters_table.php');
        $migrazione->down();

        DB::table('game_sessions')->where('id', $this->prossima->id)->update([
            'initiative' => json_encode(['round' => 3, 'turnoId' => 'a', 'combattenti' => [['id' => 'a', 'tipo' => 'mostro', 'nome' => 'Orco', 'hp' => 0]]]),
        ]);

        $migrazione->up();

        $convertito = Encounter::sole();

        expect($convertito->game_session_id)->toBe($this->prossima->id)
            ->and($convertito->round)->toBe(3)
            ->and($convertito->defeated())->toBe(['Orco'])
            ->and(Schema::hasColumn('game_sessions', 'initiative'))->toBeFalse();
    });
});

describe('le ricompense di fine sessione', function () {
    beforeEach(function () {
        $this->giocata = GameSession::factory()->for($this->campagna)->create(['played_at' => now()->subDay()]);
        $this->bruno = Character::factory()->ownedBy($altro = User::factory()->player()->create())->create(['gp' => 0]);
        $this->anna->update(['gp' => 0]);
        $this->giocata->attendees()->attach([
            $this->giocatore->id => ['character_id' => $this->anna->id],
            $altro->id => ['character_id' => $this->bruno->id],
            User::factory()->player()->create()->id => ['character_id' => null],
        ]);
    });

    it('vanno a tutti i personaggi presenti, con il motivo nel Registro', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->post(route('sessions.rewards', $this->giocata), ['coins' => ['gp' => 50], 'reason' => 'Taglia sui briganti'])
            ->assertSessionHas('status');

        expect($this->anna->refresh()->gp)->toBe(50)
            ->and($this->bruno->refresh()->gp)->toBe(50)
            ->and($this->anna->ledgerEntries()->latest('id')->value('message'))->toContain('Taglia sui briganti')
            ->and($this->giocata->refresh()->rewards)->toHaveCount(1);
    });

    it('senza motivo non partono', function () {
        $this->actingAs($this->dm)
            ->post(route('sessions.rewards', $this->giocata), ['coins' => ['gp' => 50], 'reason' => ''])
            ->assertSessionHasErrors('reason');

        expect($this->anna->refresh()->gp)->toBe(0);
    });

    it('un giocatore non le dà', function () {
        $this->actingAs($this->giocatore)
            ->post(route('sessions.rewards', $this->giocata), ['coins' => ['gp' => 50], 'reason' => 'Io'])
            ->assertForbidden();
    });
});

describe('nota di passaggio, PNG e Manuale', function () {
    it('la nota la scrive qualsiasi DM, e i giocatori non la vedono', function () {
        $altroDm = User::factory()->dm()->create(['name' => 'Morgana']);

        $this->actingAs($altroDm)
            ->put(route('dm.handover', $this->campagna), ['handover_notes' => 'Siamo alla torre, Berta mente.'])
            ->assertRedirect();

        $this->actingAs($this->dm)
            ->get(route('dm.home'))
            ->assertSee('Siamo alla torre, Berta mente.')
            ->assertSee('Aggiornata da Morgana');

        $this->actingAs($this->giocatore)->get(route('campaigns.show', $this->campagna))->assertDontSee('Berta mente');
        $this->actingAs($this->giocatore)->put(route('dm.handover', $this->campagna), ['handover_notes' => 'x'])->assertForbidden();
    });

    it('i PNG si creano dall\'app e restano ai DM', function () {
        Livewire::actingAs($this->dm)
            ->withQueryParams(['campagna' => 'valcupa'])
            ->test(NpcManager::class)
            ->call('nuovo')
            ->set('png.name', 'Berta')
            ->set('png.wants', 'Il figlio')
            ->call('salva')
            ->assertHasNoErrors()
            ->set('cerca', 'figlio')
            ->assertSee('Berta');

        expect($this->campagna->npcs()->value('name'))->toBe('Berta');

        $this->actingAs($this->giocatore)->get(route('dm.npcs'))->assertForbidden();
    });

    it('il Manuale applica la percentuale della campagna, solo al listino', function () {
        $this->campagna->update(['price_modifier' => 50]);

        expect($this->campagna->adjustedPrice(5_000))->toBe(7_500)
            ->and($this->campagna->adjustedPrice(1))->toBe(2);

        $this->actingAs($this->dm)
            ->get(route('dm.manual', ['campagna' => 'valcupa']))
            ->assertOk()
            ->assertSee('Pozione di guarigione')
            ->assertSee('+50%')
            ->assertSee('Privo di sensi');

        $this->actingAs($this->giocatore)->get(route('dm.manual'))->assertForbidden();
    });
});

describe('la Gilda con occhi da DM (M16)', function () {
    it('il DM ha la ricerca, il giocatore no', function () {
        $this->actingAs($this->dm)->get(route('guild.index'))
            ->assertOk()->assertSee('Cerca per eroe o per giocatore');

        $this->actingAs($this->giocatore)->get(route('guild.index'))
            ->assertOk()->assertDontSee('Cerca per eroe o per giocatore');
    });

    it('la ricerca del DM filtra per nome', function () {
        // Nome fisso: la ricerca guarda anche il giocatore, e un nome a caso come «Annamaria» la farebbe passare.
        Character::factory()->ownedBy(User::factory()->player()->create(['name' => 'Bruno Ferri']))->create(['name' => 'Zorblax']);

        $this->actingAs($this->dm)->get(route('guild.index', ['cerca' => 'Anna']))
            ->assertOk()->assertSee('Anna Ventochiara')->assertDontSee('Zorblax');
    });

    it('segna chi è sotto richiamo, solo al DM', function () {
        app(IssueWarning::class)->handle($this->giocatore, $this->dm, 'Motivo.');

        $this->actingAs($this->dm)->get(route('guild.index'))
            ->assertOk()->assertSee('Il giocatore è sotto richiamo');

        $this->actingAs(User::factory()->player()->create())->get(route('guild.index'))
            ->assertOk()->assertDontSee('Il giocatore è sotto richiamo');
    });
});
