<?php

declare(strict_types=1);

use App\Livewire\CharacterWizard;
use App\Models\Build;
use App\Models\Character;
use App\Models\User;
use Livewire\Livewire;

// Il wizard guida l'utente, ma CreateCharacter rivalida tutti i dati e resta il confine di sicurezza.
describe('chi può usarla', function () {
    it('un giocatore senza personaggi vivi', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.create'))
            ->assertOk()
            ->assertSee('Nome del personaggio')
            ->assertSee('Avanti');
    });

    it('ma non chi ne ha già uno', function () {
        $player = User::factory()->player()->create();
        Character::factory()->ownedBy($player)->create();

        $this->actingAs($player)
            ->get(route('characters.create'))
            ->assertForbidden();
    });

    it('e nemmeno un admin, che non gioca', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('characters.create'))
            ->assertForbidden();
    });
});

describe('il point buy', function () {
    it('parte da 27 punti con tutto a 8', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class)->assertSet('scores.str', 8);

        expect($component->instance()->remainingPoints())->toBe(27);
    });

    it('scala i punti salendo, e li restituisce scendendo', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class)
            ->call('increase', 'str')  
            ->assertSet('scores.str', 9);

        expect($component->instance()->remainingPoints())->toBe(26);

        $component->call('decrease', 'str')->assertSet('scores.str', 8);

        expect($component->instance()->remainingPoints())->toBe(27);
    });

    it('non lascia superare 15', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class);

        foreach (range(1, 10) as $ignored) {
            $component->call('increase', 'str');
        }

        $component->assertSet('scores.str', 15);
    });

    it('non lascia sforare il budget', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class)
            ->set('scores', ['str' => 15, 'dex' => 15, 'con' => 15, 'int' => 8, 'wis' => 8, 'cha' => 8]);

        $component->call('increase', 'int')->assertSet('scores.int', 8);
    });

    it('mostra i punteggi con i bonus di specie già sommati', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class)
            ->set('species', 'Mezzorco')
            ->set('scores.str', 15);

        expect($component->instance()->finalScores()['str'])->toBe(17);
    });
});

describe('l\'avanzamento fra i passi', function () {
    it('si blocca finché il passo non è completo', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->assertSet('step', 1)
            ->call('next')
            ->assertSet('step', 1)           
            ->set('name', 'Kaeleth')
            ->call('next')
            ->assertSet('step', 1)          
            ->set('class', 'Guerriero')
            ->call('next')
            ->assertSet('step', 2);
    });

    it('richiede i bonus a scelta delle specie che li hanno', function () {
        $this->actingAs(User::factory()->player()->create());

        $component = Livewire::test(CharacterWizard::class)
            ->set('step', 2)
            ->set('name', 'Kaeleth')
            ->set('class', 'Guerriero')
            ->set('species', 'Umano (Variante)')
            ->call('next')
            ->assertSet('step', 2);        

        $component->set('speciesChoices', ['str', 'con'])
            ->call('next')
            ->assertSet('step', 3);
    });

    it('pretende il numero esatto di abilità', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('class', 'Guerriero')     
            ->set('step', 5)
            ->set('skills', ['athletics'])
            ->call('next')
            ->assertSet('step', 5)
            ->set('skills', ['athletics', 'perception'])
            ->call('next')
            ->assertSet('step', 6);
    });

    it('azzera le scelte se si torna indietro a cambiare classe', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('name', 'Kaeleth')
            ->set('class', 'Ladro')
            ->set('skills', ['stealth', 'acrobatics', 'deception', 'insight'])
            ->set('step', 1)
            ->set('class', 'Chierico')
            ->call('next')
            ->assertSet('skills', [])
            ->assertSet('step', 2);
    });
});

describe('la creazione', function () {
    it('produce un personaggio completo e ci porta sulla scheda', function () {
        $player = User::factory()->player()->create();
        $this->actingAs($player);

        Livewire::test(CharacterWizard::class)
            ->set('name', 'Kaeleth')
            ->set('class', 'Guerriero')
            ->set('species', 'Mezzorco')
            ->set('scores', ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8])
            ->set('background', 'Soldato')
            ->set('skills', ['athletics', 'perception'])
            ->set('step', 7)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $character = Character::where('name', 'Kaeleth')->first();

        expect($character)->not->toBeNull()
            ->and($character->user_id)->toBe($player->id)
            ->and($character->level)->toBe(1)
            ->and($character->str)->toBe(17)         
            ->and($character->items()->count())->toBeGreaterThan(0);
    });

    it('rispetta le scelte di equipaggiamento', function () {
        $player = User::factory()->player()->create();
        $this->actingAs($player);

        Livewire::test(CharacterWizard::class)
            ->set('name', 'Arciere')
            ->set('class', 'Guerriero')
            ->set('species', 'Elfo')
            ->set('scores', ['str' => 12, 'dex' => 15, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 8])
            ->set('background', 'Soldato')
            ->set('skills', ['athletics', 'perception'])
            ->set('equipment', [0 => 1])
            ->call('save');

        $character = Character::where('name', 'Arciere')->first();

        expect($character->ownsItem('Arco Lungo'))->toBeTrue()
            ->and($character->ownsItem('Armatura di Cuoio'))->toBeTrue()
            ->and($character->ownsItem('Cotta di Maglia'))->toBeFalse();
    });

    it('mostra un errore invece di esplodere se qualcosa non torna', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('name', 'Impossibile')
            ->set('class', 'Guerriero')
            ->set('species', 'Mezzorco')
            ->set('scores', ['str' => 15, 'dex' => 15, 'con' => 15, 'int' => 15, 'wis' => 15, 'cha' => 15])
            ->set('background', 'Soldato')
            ->set('skills', ['athletics', 'perception'])
            ->call('save')
            ->assertHasErrors('creazione');

        expect(Character::where('name', 'Impossibile')->exists())->toBeFalse();
    });
});

describe('il riepilogo e le build', function () {
    it('un non incantatore salta il settimo passo, andata e ritorno', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('class', 'Guerriero')   
            ->set('step', 6)
            ->call('next')
            ->assertSet('step', 8)       
            ->call('previous')
            ->assertSet('step', 6);      
    });

    it('un incantatore invece passa dal settimo', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('class', 'Mago')
            ->set('step', 6)
            ->call('next')
            ->assertSet('step', 7);
    });

    it('parte da una build completa e atterra sul riepilogo, già compilato', function () {
        $this->actingAs(User::factory()->player()->create());
        Build::factory()->complete()->create(['slug' => 'il-muro', 'class' => 'Guerriero']);

        Livewire::withQueryParams(['build' => 'il-muro'])
            ->test(CharacterWizard::class)
            ->assertSet('step', 8)
            ->assertSet('class', 'Guerriero')
            ->assertSet('species', 'Nano')
            ->assertSet('background', 'Soldato');
    });

    it('crea il personaggio dal riepilogo di una build', function () {
        $player = User::factory()->player()->create();
        $this->actingAs($player);
        Build::factory()->complete()->create(['slug' => 'il-muro']);

        Livewire::withQueryParams(['build' => 'il-muro'])
            ->test(CharacterWizard::class)
            ->set('name', 'Grimm')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        expect(Character::where('name', 'Grimm')->exists())->toBeTrue();
    });

    it('non crea dal riepilogo di una build senza nome', function () {
        $player = User::factory()->player()->create();
        $this->actingAs($player);
        Build::factory()->complete()->create(['slug' => 'il-muro']);

        Livewire::withQueryParams(['build' => 'il-muro'])
            ->test(CharacterWizard::class)
            ->assertSet('step', 8)
            ->call('save')               
            ->assertHasErrors('creazione');

        expect(Character::query()->where('user_id', $player->id)->exists())->toBeFalse();
    });

    it('«Modifica» apre il passo e «Torna al riepilogo» riporta', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('step', 8)
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->assertSet('editing', true)
            ->set('species', 'Elfo')
            ->call('backToSummary')
            ->assertSet('step', 8)
            ->assertSet('editing', false);
    });
});
