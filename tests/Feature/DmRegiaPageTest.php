<?php

declare(strict_types=1);

use App\Actions\Users\IssueWarning;
use App\Livewire\CombatTracker;
use App\Livewire\SessionPrep;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Monster;
use App\Models\User;
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
// Il roster della Regia deriva dai personaggi registrati nelle presenze delle serate già giocate.
    $giocata = GameSession::factory()->for($this->campagna)->create([
        'number' => 1, 'played_at' => now()->subWeek(),
    ]);
    $giocata->attendees()->attach($this->giocatore->id, ['character_id' => $this->anna->id]);

    $this->prossima = GameSession::factory()->for($this->campagna)->create([
        'number' => 2, 'played_at' => now()->addWeek(),
    ]);
});

describe('la Regia (home)', function () {
    it('il DM vede la sua campagna, il tavolo e la porta della serata', function () {
        $this->actingAs($this->dm)
            ->get(route('dm.home'))
            ->assertOk()
            ->assertSee('Le Rovine di Valcupa')
            ->assertSee('Anna Ventochiara')
            ->assertSee('Conduci la serata');
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

describe('conduci la serata (sulla pagina della serata, P21)', function () {
    it('il DM del tavolo vede il tavolo e i comandi, senza pagine doppie', function () {
        $this->actingAs($this->dm)
            ->get(route('sessions.show', ['session' => $this->prossima, 'da' => 'regia']))
            ->assertOk()
            ->assertSee('Il tavolo')
            ->assertSee('Anna Ventochiara')
            ->assertSee('Prepara la serata')
            ->assertSee('Conduci tu');
    });

    it('un sostituto vede il tavolo ma non i comandi di chiusura', function () {
        $altroDm = User::factory()->dm()->create();

        $this->actingAs($altroDm)
            ->get(route('sessions.show', $this->prossima))
            ->assertOk()
            ->assertSee('Anna Ventochiara')
            ->assertDontSee('Conduci tu');
    });

    it('un giocatore non vede il tavolo da DM', function () {
        $this->actingAs($this->giocatore)
            ->get(route('sessions.show', $this->prossima))
            ->assertOk()
            ->assertDontSee('Il tavolo');
    });
});

describe('prepara la serata', function () {
    it('il DM apre la preparazione: appunti e combattimento', function () {
        $this->actingAs($this->dm)
            ->get(route('dm.prepare', $this->prossima))
            ->assertOk()
            ->assertSee('Appunti')
            ->assertSee('Popola dal tavolo');
    });

    it('un giocatore non ci entra', function () {
        $this->actingAs($this->giocatore)
            ->get(route('dm.prepare', $this->prossima))
            ->assertForbidden();
    });

    it('gli appunti si salvano sulla serata, privati', function () {
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
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->assertForbidden();
    });

    it('popola gli eroi dal tavolo, a iniziativa zero', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('popolaDalTavolo')
            ->assertHasNoErrors();

        $anna = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id);
        expect($anna)->not->toBeNull()
            ->and($anna['tipo'])->toBe('pg')
            ->and($anna['iniziativa'])->toBe(0);
    });

    it('aggiunge un mostro al volo con PF e CA', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
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
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('popolaDalTavolo');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->set("colpo.{$id}", 7)->call('danno', $id)->assertHasNoErrors();

        expect($this->anna->refresh()->hp_current)->toBe(23);
    });

    it('il danno a un mostro scende sul suo numero, non sotto zero', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->set('mostroNome', 'Goblin')->set('mostroHp', 7)->set('mostroAc', 15)
            ->call('aggiungiMostro');

        $id = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin')['id'];

        $comp->set("colpo.{$id}", 100)->call('danno', $id);

        $mob = collect($this->prossima->refresh()->initiative['combattenti'])->firstWhere('nome', 'Goblin');
        expect($mob['hp'])->toBe(0);
    });

    it('mette e toglie una condizione dalla lista fissa', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('popolaDalTavolo');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->call('condizione', $id, 'poisoned');
        $addosso = fn () => collect($this->prossima->refresh()->initiative['combattenti'])->firstWhere('id', $id)['condizioni'];
        expect($addosso())->toContain('poisoned');

        $comp->call('condizione', $id, 'poisoned');
        expect($addosso())->not->toContain('poisoned');
    });

    it('il DM segna un tiro morte a un eroe a terra', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0]);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('popolaDalTavolo');

        $id = collect($comp->get('combattenti'))->firstWhere('characterId', $this->anna->id)['id'];

        $comp->call('tiroMorte', $id, 'fallimento', 2);

        expect($this->anna->refresh()->death_save_failures)->toBe(2);
    });

    it('il giocatore segna i tiri morte sulla sua scheda: lo stesso dato', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0]);

        Livewire::actingAs($this->giocatore)
            ->test(App\Livewire\HitPointTracker::class, ['character' => $this->anna])
            ->call('tiroMorte', 'successo', 3);

        expect($this->anna->refresh()->death_save_successes)->toBe(3);
    });

    it('curare sopra zero azzera i tiri morte', function () {
        $this->anna->update(['hp_max' => 30, 'hp_current' => 0, 'death_save_failures' => 2]);

        app(App\Actions\Characters\AdjustHitPoints::class)->heal($this->anna->refresh(), 5);

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
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('aggiungiDalBestiario', $goblin->id);

        $mob = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin');
        expect($mob['tipo'])->toBe('mostro')
            ->and($mob['hp'])->toBe(7)
            ->and($mob['monsterId'])->toBe($goblin->id)
            ->and($mob['traits'])->toBe('Fuga astuta.');
    });

    it('salvando al volo, il mostro entra anche nel bestiario', function () {
        Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->set('mostroNome', 'Orco')->set('mostroHp', 15)->set('mostroAc', 13)
            ->set('salvaNelBestiario', true)
            ->call('aggiungiMostro')
            ->assertHasNoErrors();

        expect(Monster::where('name', 'Orco')->exists())->toBeTrue();
    });

    it('apre lo statblock esteso al clic', function () {
        $goblin = Monster::factory()->create(['name' => 'Goblin']);

        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('aggiungiDalBestiario', $goblin->id);

        $id = collect($comp->get('combattenti'))->firstWhere('nome', 'Goblin')['id'];

        $comp->call('apriStatblock', $id)
            ->assertSet('statblockAperto', $id)
            ->assertSee('Attacchi');
    });

    it('riordina per iniziativa quando cambia un numero in riga', function () {
        $comp = Livewire::actingAs($this->dm)
            ->test(CombatTracker::class, ['session' => $this->prossima])
            ->call('popolaDalTavolo')
            ->set('mostroNome', 'Goblin')->set('mostroHp', 7)->set('mostroAc', 15)
            ->call('aggiungiMostro');

        $combattenti = $comp->get('combattenti');
        $iAnna = collect($combattenti)->search(fn ($c) => ($c['characterId'] ?? null) === $this->anna->id);

        $comp->set("combattenti.{$iAnna}.iniziativa", 25);

        $ordine = $this->prossima->refresh()->initiative['combattenti'];
        expect($ordine[0]['nome'])->toBe('Anna Ventochiara')
            ->and($ordine[0]['iniziativa'])->toBe(25);
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
        Character::factory()->ownedBy(User::factory()->player()->create())->create(['name' => 'Zorblax']);

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
