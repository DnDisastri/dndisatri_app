<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterItemEffect;
use App\Models\CharacterSpell;
use App\Models\CharacterWeapon;
use App\Models\User;

// Il Turno usa `characters.show`; le altre sezioni hanno route dedicate per mantenere compatibili i link alla scheda.
function sezione(Character $character, string $quale): string
{
    return route('characters.section', [$character, $quale]);
}


function suoi(Character $character): User
{
    return $character->user;
}

describe('la scheda', function () {
    it('mostra i valori calcolati, non quelli salvati', function () {
        $character = Character::factory()->create([
            'name' => 'Grommash', 'class' => 'Barbaro', 'level' => 3,
            'dex' => 14, 'con' => 16, 'hp_max' => 38, 'hp_current' => 30,
        ]);
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();
        CharacterItem::factory()->for($character)->shield('Scudo')->create();

        $this->actingAs(suoi($character))
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Grommash')
            ->assertSee('18')
            ->assertSee('/ 38 PF');
    });

    it('segnala i punteggi alterati da un oggetto magico', function () {
        $character = Character::factory()->create(['str' => 12]);
        CharacterItemEffect::factory()->for($character)
            ->setTo(Ability::Str, 21, 'Cintura di Forza del Gigante')->create();

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'prove'))
            ->assertOk()
            ->assertSee('21')
            ->assertSee('base 12');
    });

    it('e il bordo dorato sta su quelle alterate, non sulle altre', function () {
        $normale = Character::factory()->create(['str' => 12]);

        $this->actingAs(suoi($normale))
            ->get(sezione($normale, 'prove'))
            ->assertOk()
            ->assertDontSee('border-on-accent-soft', false);

        $alterato = Character::factory()->create(['str' => 12]);
        CharacterItemEffect::factory()->for($alterato)
            ->setTo(Ability::Str, 21, 'Cintura di Forza del Gigante')->create();

        $this->actingAs(suoi($alterato))
            ->get(sezione($alterato, 'prove'))
            ->assertOk()
            ->assertSee('border-on-accent-soft', false);
    });

    it('mostra la foto in cima, su qualunque sezione', function () {
        $character = Character::factory()->create(['name' => 'Grommash']);
        $character->forceFill(['photo_path' => 'characters/grommash.jpg'])->save();

        foreach ([route('characters.show', $character), sezione($character, 'zaino')] as $indirizzo) {
            $this->actingAs(suoi($character))
                ->get($indirizzo)
                ->assertOk()
                ->assertSee('characters/grommash.jpg', false);
        }
    });

    it('tiene i quattro numeri in cima, su qualunque sezione', function () {
        $character = Character::factory()->create([
            'class' => 'Barbaro', 'level' => 5, 'hit_die' => 12, 'speed' => 9,
        ]);

        foreach ([route('characters.show', $character), sezione($character, 'zaino')] as $indirizzo) {
            $this->actingAs(suoi($character))
                ->get($indirizzo)
                ->assertOk()
                ->assertSee('Punti ferita')
                ->assertSee('CA')
                ->assertSee('Iniz.')
                ->assertSee('Vel.')
                ->assertSee('Comp.');
        }
    });

    it('e i dadi vita accanto al pulsante, per chi può spenderli', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'class' => 'Barbaro', 'level' => 5, 'hit_die' => 12,
        ]);

        $this->actingAs($player)
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Riposo e dadi vita')
            ->assertSee('5/5 dadi disponibili');

        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertDontSee('dadi disponibili');
    });

    it('nel Turno ogni riga porta il suo numero già fatto', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 5, 'spell_ability' => 'int', 'int' => 18,
        ]);
        $character->addToInventory('Pugnale');
        CharacterSpell::factory()->for($character)->create(['name' => 'Dardo di Fuoco', 'level' => 0]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Spruzzo Acido', 'level' => 0]);

        $this->actingAs(suoi($character))
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('colpire')
            ->assertSee('+7')
            ->assertSee('DES 15');
    });


    it('e l\'equipaggiamento sta nello zaino, coi tre slot sempre visibili', function () {
        $character = Character::factory()->create();
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'zaino'))
            ->assertOk()
            ->assertSee('Equipaggiamento')
            ->assertSee('Cotta di Maglia')
            ->assertSee('equipaggiato')
            ->assertSee('Scudo')
            ->assertSee('niente');
    });

    it('e la storia si legge in «Storia»', function () {
        $character = Character::factory()->create();
        $character->forceFill([
            'story' => 'Cresciuto fra i ghiacci, scese a valle per una promessa.',
        ])->save();

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'storia'))
            ->assertOk()
            ->assertSee('Cresciuto fra i ghiacci');

        $vuoto = Character::factory()->create();

        $this->actingAs(suoi($vuoto))
            ->get(sezione($vuoto, 'storia'))
            ->assertOk()
            ->assertDontSee('La storia')
            ->assertSee('non è ancora stato scritto niente');
    });

    it('mostra il bonus di attacco delle armi', function () {
        $character = Character::factory()->create(['str' => 18, 'level' => 5]);
        $character->addToInventory('Spadone');
        CharacterWeapon::factory()->for($character)->create([
            'name' => 'Spadone', 'attack_ability' => Ability::Str,
            'weapon_bonus' => 1, 'damage' => '2d6+4',
        ]);

        $this->actingAs(suoi($character))
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Spadone')
            ->assertSee('+8')
            ->assertSee('2d6+4');
    });

    it('e la descrizione di un incantesimo si legge, non si scopre col mouse', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'spell_ability' => 'int']);

        CharacterSpell::factory()->for($character)->create([
            'name' => 'Grimorio di Nonno', 'level' => 1,
            'description' => 'Quello che ha scritto il giocatore.',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Dardo di Fuoco', 'level' => 0]);

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'magia'))
            ->assertOk()
            ->assertSee('Quello che ha scritto il giocatore.')
            ->assertSee('Trucchetto di Evocazione', false)
            ->assertDontSee('title="Quello che ha scritto il giocatore."', false);
    });

    it('chi prepara sceglie la lista del giorno, con il contatore', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Chierico', 'level' => 3, 'wis' => 16, 'spell_ability' => 'wis',
        ]);

        CharacterSpell::factory()->for($character)->create(['name' => 'Cura Ferite', 'level' => 1, 'prepared' => false]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Benedizione', 'level' => 1, 'prepared' => false]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\SpellBook::class, ['character' => $character])
            ->assertSee('Pronti per oggi')
            ->set('preparati', ['Cura Ferite'])
            ->assertHasNoErrors();

        expect($character->spells()->where('prepared', true)->pluck('name')->all())
            ->toBe(['Cura Ferite']);
    });

    it('e oltre il limite lo dice, senza far finta di aver salvato', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Chierico', 'level' => 1, 'wis' => 10, 'spell_ability' => 'wis',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Cura Ferite', 'level' => 1, 'prepared' => false]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Benedizione', 'level' => 1, 'prepared' => false]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\SpellBook::class, ['character' => $character])
            ->set('preparati', ['Cura Ferite', 'Benedizione'])
            ->assertHasErrors('preparazione')
            ->assertSet('preparati', []);

        expect($character->spells()->where('prepared', true)->count())->toBe(0);
    });

    it('e chi non prepara non vede nessuna casella', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 3, 'spell_ability' => 'int',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Dardo Incantato', 'level' => 1]);

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'magia'))
            ->assertOk()
            ->assertSee('Dardo Incantato')
            ->assertDontSee('Pronti per oggi');
    });

    it('mostra incantesimi e slot a chi lancia', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 5, 'int' => 18, 'spell_ability' => 'int',
            'spell_slots_used' => [1 => 2],
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Palla di Fuoco', 'level' => 3]);

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'magia'))
            ->assertOk()
            ->assertSee('Palla di Fuoco')
            ->assertSee('15')
            ->assertSee('2/4');
    });


    it('non mostra la sezione magia a chi non lancia', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'subclass' => null]);

        $this->actingAs(suoi($character))
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertDontSee('Magia');

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'magia'))
            ->assertNotFound();
    });

    it('segna un personaggio caduto', function () {
        $character = Character::factory()->fallen()->create();

        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Caduto il');
    });
});

describe('lanciare un incantesimo dal Turno', function () {
    it('mostra i castabili, divisi per tiro e con le colonne dedotte', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Mago', 'level' => 5, 'int' => 18, 'spell_ability' => 'int',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Onda Tonante', 'level' => 1]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Raggio Rovente', 'level' => 2]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Armatura Magica', 'level' => 1]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\CastableSpells::class, ['character' => $character])
            ->set('aperto', true)
            ->assertSee('Tiro salvezza del bersaglio')
            ->assertSee('Onda Tonante')
            ->assertSee('COS 15')        
            ->assertSee('Cubo 4,5 m')    
            ->assertSee('Tiri tu per colpire')
            ->assertSee('Raggio Rovente')
            ->assertSee('+7')       
            ->assertSee('Nessun tiro')
            ->assertSee('Armatura Magica');
    });

    it('nasconde ciò che non hai slot per lanciare', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Mago', 'level' => 1, 'int' => 16, 'spell_ability' => 'int',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Dardo Incantato', 'level' => 1]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Palla di Fuoco', 'level' => 3]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\CastableSpells::class, ['character' => $character])
            ->set('aperto', true)
            ->assertSee('Dardo Incantato')
            ->assertDontSee('Palla di Fuoco')
            ->assertSee('Nascosti 1');
    });

    it('spendere l\'ultimo slot fa sparire l\'incantesimo', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Mago', 'level' => 1, 'int' => 16, 'spell_ability' => 'int',
        ]);
        CharacterSpell::factory()->for($character)->create(['name' => 'Dardo Incantato', 'level' => 1]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\CastableSpells::class, ['character' => $character])
            ->set('aperto', true)
            ->assertSee('Dardo Incantato')
            ->call('spend', 1)
            ->assertSee('Dardo Incantato')   
            ->call('spend', 1)
            ->assertDontSee('Dardo Incantato') 
            ->assertSee('niente slot');

        expect($character->fresh()->spell_slots_used)->toBe([1 => 2]);
    });

    it('un incantesimo che si potenzia chiede con che slot lanciarlo', function () {
        $giocatore = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Mago', 'level' => 3, 'int' => 16, 'spell_ability' => 'int',
        ]);

        $dardo = CharacterSpell::factory()->for($character)->create(['name' => 'Dardo Incantato', 'level' => 1]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\CastableSpells::class, ['character' => $character])
            ->set('aperto', true)
            ->call('cast', $dardo->id, 1, true)
            ->assertSet('scelta', $dardo->id)   
            ->call('castAt', 2)                
            ->assertSet('scelta', null);

        expect($character->fresh()->spell_slots_used)->toBe([2 => 1]);
    });

    it('ma se lo slot è uno solo, o non si potenzia, lancia senza chiedere', function () {
        $giocatore = User::factory()->player()->create();

        $character = Character::factory()->ownedBy($giocatore)->create([
            'class' => 'Mago', 'level' => 3, 'int' => 16, 'spell_ability' => 'int',
        ]);
        $onda = CharacterSpell::factory()->for($character)->create(['name' => 'Onda Tonante', 'level' => 1]);

        Livewire::actingAs($giocatore)
            ->test(App\Livewire\CastableSpells::class, ['character' => $character])
            ->set('aperto', true)
            ->call('cast', $onda->id, 1, false)
            ->assertSet('scelta', null);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 1]);
    });

    it('il Turno mostra «Lancia un incantesimo» solo a chi ha slot', function () {

        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3, 'spell_ability' => 'int']);
        $this->actingAs(suoi($mago))
            ->get(route('characters.show', $mago))
            ->assertOk()
            ->assertSee('Lancia un incantesimo');

        $barbaro = Character::factory()->create(['class' => 'Barbaro', 'subclass' => null]);
        $this->actingAs(suoi($barbaro))
            ->get(route('characters.show', $barbaro))
            ->assertOk()
            ->assertDontSee('Lancia un incantesimo');
    });
});

// Ogni sezione carica solo le relazioni necessarie; `preventLazyLoading` intercetta dipendenze dimenticate.
describe('le sezioni', function () {
    it('la striscia c\'è, e accende quella aperta', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'spell_ability' => 'int']);

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'zaino'))
            ->assertOk()
            ->assertSee('Turno')
            ->assertSee('Prove')
            ->assertSee('Magia')
            ->assertSee('Zaino')
            ->assertSee('Storia')
            ->assertSeeHtml('aria-current="page"');
    });

    it('i punti ferita e la classe armatura si vedono ovunque', function () {
        $character = Character::factory()->create(['hp_current' => 30, 'hp_max' => 38]);
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();

        foreach (['prove', 'zaino', 'storia'] as $quale) {
            $this->actingAs(suoi($character))
                ->get(sezione($character, $quale))
                ->assertOk()
                ->assertSee('/ 38 PF')
                ->assertSee('CA');
        }
    });

    it('e ognuna si apre da sola, senza le relazioni delle altre', function (string $quale) {
        $character = Character::factory()->create(['class' => 'Mago', 'spell_ability' => 'int']);
        CharacterWeapon::factory()->for($character)->create(['attack_ability' => Ability::Str]);
        CharacterSpell::factory()->for($character)->create();
        CharacterItem::factory()->for($character)->create();

        $this->actingAs(suoi($character))
            ->get(sezione($character, $quale))
            ->assertOk();
    })->with(['prove', 'magia', 'zaino', 'storia']);

    it('e una sezione inventata non esiste', function () {
        $character = Character::factory()->create();

        $this->actingAs(User::factory()->player()->create())
            ->get('/personaggi/'.$character->id.'/pozioni')
            ->assertNotFound();
    });

    it('e in cima c\'è da dove si è venuti', function () {
        $character = Character::factory()->create();

        $this->actingAs(suoi($character))
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Torna ai miei eroi')
            ->assertSee(route('characters.index'), false);

        foreach ([User::factory()->player()->create(), User::factory()->dm()->create()] as $altro) {
            $this->actingAs($altro)
                ->get(route('characters.show', $character))
                ->assertOk()
                ->assertSee('Torna alla Gilda')
                ->assertSee(route('guild.index'), false);
        }
    });
});
// La vista di un altro giocatore espone solo Storia e vetrina, senza caricare dati privati o di combattimento.
describe('la scheda di un altro', function () {
    beforeEach(function () {
        $this->giocatore = User::factory()->player()->create();
        $this->pg = Character::factory()->ownedBy($this->giocatore)->create([
            'name' => 'Elandra', 'class' => 'Mago', 'spell_ability' => 'int',
            'level' => 5, 'str' => 8, 'gp' => 250, 'speed' => 9,
        ]);
        $this->pg->forceFill([
            'story' => 'Scese a valle per una promessa.',
            'notes' => 'Non fidarti del locandiere.',
            'class_features' => 'Recupero Arcano al terzo livello.',
        ])->save();

        $this->pg->addToInventory('Pugnale');
        $this->pg->addToInventory('Corda di Seta');
        CharacterSpell::factory()->for($this->pg)->create(['name' => 'Dardo Incantato']);

        $this->estraneo = User::factory()->player()->create();
    });

    it('ha due linguette invece di cinque', function () {
        $this->actingAs($this->estraneo)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertSee('Storia')
            ->assertSee('Zaino')
            ->assertDontSee('Turno')
            ->assertDontSee('Prove')
            ->assertDontSee('Magia');
    });
// Le sezioni non autorizzate devono risultare inesistenti, non soltanto nascoste dall'interfaccia.
    it('e le altre, chiamate per indirizzo, non ci sono proprio', function (string $quale) {
        $this->actingAs($this->estraneo)
            ->get(sezione($this->pg, $quale))
            ->assertNotFound();
    })->with(['prove', 'magia']);


    it('e l\'indirizzo della scheda si apre sulla Storia', function () {
        $this->actingAs($this->estraneo)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertSee('Scese a valle per una promessa.');
    });

    it('ma chi lo gioca le apre tutte e cinque', function () {
        $this->actingAs($this->giocatore)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertSee('Turno')
            ->assertSee('Prove')
            ->assertSee('Magia');

        foreach (['prove', 'magia', 'zaino', 'storia'] as $quale) {
            $this->actingAs($this->giocatore)->get(sezione($this->pg, $quale))->assertOk();
        }
    });

    it('e chi conduce pure', function (string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get(sezione($this->pg, 'prove'))
            ->assertOk();
    })->with(['dm', 'admin']);

    it('e in cima non c\'è nessun numero', function () {
        $this->actingAs($this->estraneo)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertDontSee('Punti ferita')
            ->assertDontSee('Iniz.')
            ->assertDontSee('Vel.')
            ->assertDontSee('Comp.')
            ->assertDontSee('PF');
    });

    it('della Storia resta la storia, e solo quella', function () {
        $this->actingAs($this->estraneo)
            ->get(sezione($this->pg, 'storia'))
            ->assertOk()
            ->assertSee('Scese a valle per una promessa.')
            ->assertDontSee('Non fidarti del locandiere.')
            ->assertDontSee('Recupero Arcano')
            ->assertDontSee('Privilegi di classe');

        $this->actingAs($this->giocatore)
            ->get(sezione($this->pg, 'storia'))
            ->assertOk()
            ->assertSee('Non fidarti del locandiere.')
            ->assertSee('Recupero Arcano');
    });

    it('e senza storia scritta lo dice, invece di una pagina vuota', function () {
        $muto = Character::factory()->create(['name' => 'Taciturno']);

        $this->actingAs($this->estraneo)
            ->get(route('characters.show', $muto))
            ->assertOk()
            ->assertSee('non è ancora stata scritta la storia');
    });
// La vetrina pubblica contiene solo gli oggetti che il proprietario ha esplicitamente dichiarato scambiabili.
    it('dello Zaino resta la sola vetrina', function () {
        $this->pg->items()->where('name', 'Corda di Seta')->update(['tradeable' => true]);

        $this->actingAs($this->estraneo)
            ->get(sezione($this->pg, 'zaino'))
            ->assertOk()
            ->assertSee('La sua vetrina')
            ->assertSee('Corda di Seta')
            ->assertDontSee('Pugnale')
            ->assertDontSee('Equipaggiamento')
            ->assertDontSee('Scambierei');
    });

    it('e se non ha messo niente in vetrina, lo dice — e non invita a proporre il nulla', function () {
        $this->actingAs($this->estraneo)
            ->get(sezione($this->pg, 'zaino'))
            ->assertOk()
            ->assertSee('Non ha messo niente in vetrina.')
            ->assertDontSee('Proponigli uno scambio');
    });

    it('e da lì si propone uno scambio, con lui già scelto', function () {
        $this->pg->items()->where('name', 'Corda di Seta')->update(['tradeable' => true]);

        $this->actingAs($this->estraneo)
            ->get(sezione($this->pg, 'zaino'))
            ->assertOk()
            ->assertSee('Proponigli uno scambio')
            ->assertSee(route('market.trades', ['a' => $this->pg->id]), false);
    });

    it('e l\'oro non si vede da nessuna parte', function () {
        foreach (['storia', 'zaino'] as $quale) {
            $this->actingAs($this->estraneo)
                ->get(sezione($this->pg, $quale))
                ->assertOk()
                ->assertDontSee('250');
        }
    });


    it('resta chi è', function () {
        $this->actingAs($this->estraneo)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertSee('Elandra')
            ->assertSee('Mago')
            ->assertSee('Livello 5')
            ->assertSee('Scese a valle per una promessa.')
            ->assertDontSee('Dardo Incantato')
            ->assertDontSee('Pugnale');
    });
});
// I testi inseriti dagli utenti devono essere escaped per impedire l'esecuzione di HTML o script arbitrari.
describe('i testi liberi', function () {
    it('non eseguono HTML scritto dai giocatori', function () {

        $character = Character::factory()->create([
            'notes' => '<script>alert(1)</script> nota innocua',
        ]);

        $response = $this->actingAs(suoi($character))
            ->get(sezione($character, 'storia'));

        $response->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee('nota innocua');
    });

    it('ma mandano a capo dove il giocatore è andato a capo', function () {
        $character = Character::factory()->create(['notes' => "prima riga\nseconda riga"]);

        $this->actingAs(suoi($character))
            ->get(sezione($character, 'storia'))
            ->assertOk()
            ->assertSee('<br />', escape: false);
    });
});

describe('la Gilda', function () {

    it('elenca tutti, i vivi in cima e i caduti in fondo', function () {
        Character::factory()->create(['name' => 'Elandra']);
        Character::factory()->fallen()->create(['name' => 'Povero Yorick']);

        $html = $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.index'))
            ->assertOk()
            ->assertSee('Elandra')
            ->assertSee('Povero Yorick')
            ->assertSee('Hall of Fallen Heroes')
            ->getContent();

        expect(strpos($html, 'Elandra'))->toBeLessThan(strpos($html, 'Povero Yorick'));
    });

    it('e senza caduti la sezione non compare', function () {
        Character::factory()->create(['name' => 'Elandra']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.index'))
            ->assertOk()
            ->assertDontSee('Hall of Fallen Heroes');
    });

    it('mostra foto, nome e livello, e non i numeri della serata', function () {
        $character = Character::factory()->create([
            'name' => 'Grommash', 'level' => 3, 'hp_current' => 7, 'hp_max' => 38,
        ]);
        $character->forceFill(['photo_path' => 'characters/grommash.jpg'])->save();

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.index'))
            ->assertOk()
            ->assertSee('characters/grommash.jpg', false)
            ->assertSee('Grommash')
            ->assertSee('liv. 3')
            ->assertDontSee('7/38');
    });

    it('e di un multiclasse mostra il livello totale, non le singole classi', function () {
        $character = Character::factory()->create(['name' => 'Vex', 'class' => 'Ladro', 'level' => 5]);
        $character->classes()->createMany([
            ['class' => 'Ladro', 'subclass' => 'Furfante Arcano', 'level' => 3, 'is_primary' => true],
            ['class' => 'Mago', 'level' => 2, 'is_primary' => false],
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.index'))
            ->assertOk()
            ->assertSee('Vex')
            ->assertSee('liv. 5')
            ->assertDontSee('Furfante Arcano');
    });

    it('e il vecchio indirizzo dei caduti porta lì, invece di sparire', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.fallen'))
            ->assertRedirect('/gilda#caduti');
    });

    it('la card di un caduto è quella dei vivi, ma porta al memoriale', function () {
        $caduto = Character::factory()->fallen()->create([
            'name' => 'Povero Yorick', 'race' => 'Halfling', 'class' => 'Bardo', 'level' => 4,
        ]);
        $caduto->forceFill(['photo_path' => 'characters/yorick.jpg'])->save();

        $this->actingAs(User::factory()->player()->create())
            ->get(route('guild.index'))
            ->assertOk()
            ->assertSee('Povero Yorick')
            ->assertSee('liv. 4')
            ->assertSee('grayscale', false)
            ->assertSee('Caduto il')
            ->assertSee(route('fallen.show', $caduto), false);
    });

    it('sono chiuse agli ospiti', function () {
        $this->get(route('guild.index'))->assertRedirect(route('login'));
        $this->get(route('characters.show', Character::factory()->create()))
            ->assertRedirect(route('login'));
    });
});
