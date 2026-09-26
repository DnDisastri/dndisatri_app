<?php

declare(strict_types=1);

use App\Domain\Dnd\Features;
use App\Enums\ActionCost;
use App\Models\Character;
use App\Models\User;


describe('quali privilegi ha un personaggio', function () {
    it('quelli del suo livello e di quelli sotto, non gli altri', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 5]);
        $character->classes()->create([
            'class' => 'Barbaro', 'level' => 5, 'is_primary' => true,
        ]);

        $nomi = Features::for($character)->pluck('nome');

        expect($nomi)->toContain('Ira')          
            ->toContain('Attacco irruento')     
            ->toContain('Attacco extra')          
            ->not->toContain('Istinto ferino')    
            ->not->toContain('Ira implacabile'); 
    });

// In un multiclasse i privilegi dipendono dal livello nella singola classe, non dal livello totale del personaggio.
    it('nel multiclasse ogni classe conta il suo livello', function () {
        $character = Character::factory()->create(['class' => 'Guerriero', 'level' => 8]);
        $character->classes()->create([
            'class' => 'Guerriero', 'level' => 5, 'is_primary' => true,
        ]);
        $character->classes()->create([
            'class' => 'Ladro', 'level' => 3, 'is_primary' => false,
        ]);

        $nomi = Features::for($character)->pluck('nome');

        expect($nomi)->toContain('Attacco extra')      
            ->toContain('Azione scaltra')            
            ->not->toContain('Schivata prodigiosa');   
    });

    it('e si vede da quale delle due arriva', function () {
        $character = Character::factory()->create(['class' => 'Guerriero', 'level' => 3]);
        $character->classes()->create([
            'class' => 'Guerriero', 'level' => 3, 'is_primary' => true,
        ]);
        $character->classes()->create([
            'class' => 'Barbaro', 'level' => 1, 'is_primary' => false,
        ]);

        $origini = Features::for($character)->pluck('origine', 'nome');

        expect($origini['Recuperare energie'])->toBe('Guerriero')
            ->and($origini['Ira'])->toBe('Barbaro');
    });

    it('la sottoclasse porta i suoi, al livello giusto', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 6]);
        $character->classes()->create([
            'class' => 'Barbaro', 'subclass' => 'Cammino del Berserker',
            'level' => 6, 'is_primary' => true,
        ]);

        $nomi = Features::for($character)->pluck('nome');

        expect($nomi)->toContain('Frenesia')                 
            ->toContain('Ira incontenibile')               
            ->not->toContain('Presenza intimidatoria');     
    });

// Le sottoclassi possono contenere valori liberi: se mancano dati noti, la scheda deve segnalarlo invece di ometterli silenziosamente.
    it('dice quali sottoclassi non abbiamo scritto', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 3]);
        $character->classes()->create([
            'class' => 'Barbaro', 'subclass' => 'Cammino del Fornaio Iracondo',
            'level' => 3, 'is_primary' => true,
        ]);

        expect(Features::sottoclassiSenzaPrivilegi($character))->toBe(['Cammino del Fornaio Iracondo']);
    });

    it('e non si lamenta di quelle che ci sono', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 3]);
        $character->classes()->create([
            'class' => 'Barbaro', 'subclass' => 'Cammino del Berserker',
            'level' => 3, 'is_primary' => true,
        ]);

        expect(Features::sottoclassiSenzaPrivilegi($character))->toBe([]);
    });

    it('funziona anche senza le righe delle classi, dalla scheda', function () {

        $character = Character::factory()->create([
            'class' => 'Ladro', 'subclass' => 'Ladro', 'level' => 3,
        ]);

        $nomi = Features::for($character)->pluck('nome');

        expect($nomi)->toContain('Attacco furtivo')
            ->toContain('Mani veloci');
    });
});

describe('raggruppati per quanto costano', function () {
    it('azione, azione bonus, reazione, e poi le passive', function () {
        $character = Character::factory()->create(['class' => 'Ladro', 'level' => 5]);
        $character->classes()->create([
            'class' => 'Ladro', 'level' => 5, 'is_primary' => true,
        ]);

        $gruppi = Features::perCosto($character);

        expect($gruppi->keys()->all())->toBe(['bonus', 'reazione', 'passivo'])
            ->and($gruppi['bonus']->pluck('nome'))->toContain('Azione scaltra')
            ->and($gruppi['reazione']->pluck('nome'))->toContain('Schivata prodigiosa');
    });

    it('i gruppi vuoti non compaiono', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 1]);
        $character->classes()->create([
            'class' => 'Barbaro', 'level' => 1, 'is_primary' => true,
        ]);

        expect(Features::perCosto($character)->keys()->all())->toBe(['bonus', 'passivo']);
    });
});

describe('nella scheda', function () {
    it('la sezione Turno dice cosa puoi fare, e quanto costa', function () {
        $character = Character::factory()->create(['class' => 'Barbaro', 'level' => 2]);
        $character->classes()->create([
            'class' => 'Barbaro', 'level' => 2, 'is_primary' => true,
        ]);

        $this->actingAs($character->user)
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee(ActionCost::Bonus->label())
            ->assertSee('Ira')
            ->assertSee('Percezione del pericolo');
    });


    it('e ricorda a chiunque le azioni che tutti possono fare', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Generali')
            ->assertSee('Disingaggiare')
            ->assertSee('Schivare')
            ->assertSee('Attacco d\'opportunità');
    });

    it('e avverte quando di una sottoclasse non abbiamo i privilegi', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 3]);
        $character->classes()->create([
            'class' => 'Mago', 'subclass' => 'Scuola del Ritardo', 'level' => 3, 'is_primary' => true,
        ]);

        $this->actingAs($character->user)
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Scuola del Ritardo')
            ->assertSee('non abbiamo ancora scritto i privilegi');
    });


    it('e segna i riassunti ancora da ricontrollare', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 2]);
        $character->classes()->create([
            'class' => 'Mago', 'subclass' => 'Cronurgia', 'level' => 2, 'is_primary' => true,
        ]);

        $this->actingAs($character->user)
            ->get(route('characters.show', $character))
            ->assertOk()
            ->assertSee('Scarto temporale')
            ->assertSee('Da ricontrollare sul manuale');
    });
});

// Questi test proteggono la corrispondenza tra i nomi configurati e i privilegi, evitando feature irraggiungibili per errori nei dati.
describe('i dati stessi', function () {
    it('coprono tutte e dodici le classi e tutte e 108 le sottoclassi', function () {
        $privilegi = config('dnd.features');

        $sottoclassi = collect(config('dnd.subclasses'))
            ->flatten(1)
            ->pluck('name');

        expect(array_keys($privilegi['classi']))
            ->toHaveCount(count(config('dnd.classes.list')))
            ->and($sottoclassi->diff(array_keys($privilegi['sottoclassi']))->all())->toBe([]);
    });

    it('e i nomi delle sottoclassi combaciano con quelli dell\'elenco', function () {
        $noti = collect(config('dnd.subclasses'))->flatten(1)->pluck('name')->all();

        expect(array_diff(array_keys(config('dnd.features.sottoclassi')), $noti))->toBe([]);
    });

    it('e ogni privilegio dice quanto costa, con una parola che esiste', function () {
        $costi = collect(config('dnd.features.classi'))
            ->concat(config('dnd.features.sottoclassi'))
            ->flatten(1)
            ->pluck('costo')
            ->unique();

        foreach ($costi as $costo) {
            expect(ActionCost::tryFrom($costo))->not->toBeNull("«{$costo}» non è un costo valido");
        }
    });

    it('e nessuno è senza nome o senza testo', function () {
        $vuoti = collect(config('dnd.features.classi'))
            ->concat(config('dnd.features.sottoclassi'))
            ->flatten(1)
            ->filter(fn (array $p) => blank($p['nome'] ?? null) || blank($p['testo'] ?? null));

        expect($vuoti)->toBeEmpty();
    });
});
