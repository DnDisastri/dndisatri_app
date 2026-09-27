<?php

declare(strict_types=1);

use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\ClassRules;
use App\Filament\Resources\Subclasses\Pages\CreateSubclass;
use App\Filament\Resources\Subclasses\Pages\ListSubclasses;
use App\Filament\Resources\Subclasses\SubclassResource;
use App\Models\Subclass;
use App\Models\User;
use Database\Seeders\SubclassSeeder;
use Livewire\Livewire;

// Il catalogo sta in tabella, non più in config/dnd/subclasses.php: il gruppo
// può aggiungerne di proprie senza toccare il codice.

describe('il catalogo', function () {
    it('arriva dal seeder con tutte le sottoclassi del manuale', function () {
        $this->seed(SubclassSeeder::class);

        $attese = collect(config('dnd.subclasses'))->flatten(1)->count();

        expect(Subclass::count())->toBe($attese)
            ->and(Subclass::where('is_homebrew', true)->count())->toBe(0);
    });

    it('rigirare il seeder non duplica niente', function () {
        $this->seed(SubclassSeeder::class);
        $prima = Subclass::count();

        $this->seed(SubclassSeeder::class);

        expect(Subclass::count())->toBe($prima);
    });

    it('conserva l\'ordine del manuale', function () {
        $this->seed(SubclassSeeder::class);

        $dalManuale = collect(config('dnd.subclasses.Barbaro'))->pluck('name')->all();

        expect(ClassRules::subclasses('Barbaro'))->toBe($dalManuale);
    });

    // Era una lista a parte in config/dnd/classes.php: il seeder deve averla
    // trasformata nel contrassegno sulle due righe giuste, e su nessun'altra.
    it('porta con sé chi lancia pur stando in una classe che non lancia', function () {
        $this->seed(SubclassSeeder::class);

        expect(CasterType::for('Guerriero', 'Cavaliere Mistico'))->toBe(CasterType::Third)
            ->and(CasterType::for('Ladro', 'Furfante Arcano'))->toBe(CasterType::Third)
            ->and(CasterType::for('Guerriero', 'Campione'))->toBe(CasterType::None)
            ->and(CasterType::for('Ladro', 'Assassino'))->toBe(CasterType::None);
    });
});

describe('una sottoclasse aggiunta dal pannello', function () {
    it('si può scegliere subito, senza toccare il codice', function () {
        Subclass::factory()->of('Guerriero')->create(['name' => 'Cavaliere del Vuoto']);

        expect(ClassRules::subclasses('Guerriero'))->toContain('Cavaliere del Vuoto');
    });

    // Era una lista dentro config/dnd/classes.php: senza il contrassegno
    // sulla riga, una homebrew che lancia non potrebbe esistere.
    it('può lanciare incantesimi anche in una classe che non lancia', function () {
        Subclass::factory()->of('Guerriero')->thirdCaster()->create(['name' => 'Guerriero Psionico']);

        expect(CasterType::for('Guerriero', 'Guerriero Psionico'))->toBe(CasterType::Third)
            ->and(CasterType::for('Guerriero', 'Campione'))->toBe(CasterType::None);
    });

    it('e se non lancia, la classe resta senza incantesimi', function () {
        Subclass::factory()->of('Guerriero')->create(['name' => 'Cavaliere del Vuoto']);

        expect(CasterType::for('Guerriero', 'Cavaliere del Vuoto'))->toBe(CasterType::None);
    });
});

describe('il pannello', function () {
    it('lo aprono DM e admin', function ($ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get(SubclassResource::getUrl('index'))
            ->assertOk();
    })->with(['dm', 'admin']);

    it('e resta chiuso ai giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(SubclassResource::getUrl('index'))
            ->assertForbidden();
    });

    it('da lì si aggiunge una sottoclasse', function () {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CreateSubclass::class)
            ->fillForm([
                'class' => 'Guerriero',
                'name' => 'Cavaliere del Vuoto',
                'description' => 'Combatte con le ombre.',
                'is_homebrew' => true,
                'position' => 99,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(ClassRules::subclasses('Guerriero'))->toContain('Cavaliere del Vuoto');
    });

    // Ci sono personaggi che le hanno: toglierle dall'elenco vorrebbe dire
    // non poterle più scegliere per un capriccio di chi passa di lì.
    it('quelle del manuale non si cancellano', function () {
        $daManuale = Subclass::factory()->fromTheBook()->create();
        $homebrew = Subclass::factory()->create();
        $admin = User::factory()->admin()->create();

        expect($admin->can('delete', $daManuale))->toBeFalse()
            ->and($admin->can('delete', $homebrew))->toBeTrue();

        Livewire::actingAs($admin)
            ->test(ListSubclasses::class)
            ->assertTableActionHidden('delete', $daManuale)
            ->assertTableActionVisible('delete', $homebrew);
    });
});
