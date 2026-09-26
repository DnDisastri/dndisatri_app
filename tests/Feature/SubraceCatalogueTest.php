<?php

declare(strict_types=1);

use App\Actions\Characters\CreateCharacter;
use App\Domain\Dnd\PointBuy;
use App\Domain\Dnd\SubraceCatalogue;
use App\Filament\Resources\Subraces\Pages\ListSubraces;
use App\Filament\Resources\Subraces\SubraceResource;
use App\Livewire\CharacterWizard;
use App\Models\Subrace;
use App\Models\User;
use Database\Seeders\SubraceSeeder;
use Livewire\Livewire;

// Le sottorazze non sono solo un nome: portano bonus che si sommano a quelli
// della razza, e a volte una velocità diversa.
$punteggi = ['str' => 8, 'dex' => 15, 'con' => 14, 'int' => 13, 'wis' => 12, 'cha' => 10];

describe('i bonus', function () use ($punteggi) {
    it('si sommano a quelli della razza, non li sostituiscono', function () use ($punteggi) {
        $this->seed(SubraceSeeder::class);

        $finali = PointBuy::withSpecies($punteggi, 'Elfo', [], 'Elfo Alto');

        // +2 Destrezza dall'elfo, +1 Intelligenza dalla sottorazza.
        expect($finali['dex'])->toBe(17)
            ->and($finali['int'])->toBe(14);
    });

    it('senza sottorazza restano quelli della razza', function () use ($punteggi) {
        $finali = PointBuy::withSpecies($punteggi, 'Elfo');

        expect($finali['dex'])->toBe(17)
            ->and($finali['int'])->toBe(13);
    });

    // La discendenza draconica decide soffio e resistenza, non i punteggi.
    it('una scelta senza bonus non tocca niente', function () use ($punteggi) {
        Subrace::factory()->of('Dragonide')->withoutBonuses()->create(['name' => 'Rosso']);

        $finali = PointBuy::withSpecies($punteggi, 'Dragonide', [], 'Rosso');

        expect($finali)->toBe(PointBuy::withSpecies($punteggi, 'Dragonide'));
    });
});

describe('il catalogo', function () {
    it('arriva dal seeder', function () {
        $this->seed(SubraceSeeder::class);

        $catalogo = app(SubraceCatalogue::class);

        expect($catalogo->of('Elfo')->pluck('name')->all())
            ->toBe(['Elfo Alto', 'Elfo dei Boschi', 'Drow'])
            ->and($catalogo->of('Dragonide'))->toHaveCount(10)
            ->and($catalogo->required('Tiefling'))->toBeFalse();
    });

    it('rigirare il seeder non duplica niente', function () {
        $this->seed(SubraceSeeder::class);
        $prima = Subrace::count();

        $this->seed(SubraceSeeder::class);

        expect(Subrace::count())->toBe($prima);
    });

    it('solo l\'elfo dei boschi cambia il passo', function () {
        $this->seed(SubraceSeeder::class);

        $catalogo = app(SubraceCatalogue::class);

        expect($catalogo->find('Elfo', 'Elfo dei Boschi')->speed)->toBe(10.5)
            ->and($catalogo->find('Elfo', 'Drow')->speed)->toBeNull();
    });
});

describe('la creazione del personaggio', function () use ($punteggi) {
    it('applica bonus, velocità e tratti della sottorazza', function () use ($punteggi) {
        $this->seed(SubraceSeeder::class);

        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Thalia',
            class: 'Ranger',
            species: 'Elfo',
            background: 'Accolito',
            boughtScores: $punteggi,
            skills: ['athletics', 'perception', 'survival'],
            subspecies: 'Elfo dei Boschi',
        );

        expect($eroe->subrace)->toBe('Elfo dei Boschi')
            ->and($eroe->wis)->toBe(13)          // 12 + 1 dalla sottorazza
            ->and($eroe->dex)->toBe(17)          // 15 + 2 dall'elfo
            ->and((float) $eroe->speed)->toBe(10.5)
            ->and($eroe->species_traits)->toContain('+1 Saggezza');
    });

    it('e senza sottorazza la scheda resta come prima', function () use ($punteggi) {
        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Borin',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Accolito',
            boughtScores: $punteggi,
            skills: ['acrobatics', 'athletics'],
        );

        expect($eroe->subrace)->toBeNull()
            ->and((float) $eroe->speed)->toBe(9.0);
    });
});

describe('il wizard', function () {
    it('non lascia andare avanti finché la sottorazza non è scelta', function () {
        $this->seed(SubraceSeeder::class);

        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('step', 2)
            ->call('selectSpecies', 'Elfo');

        expect($componente->instance()->canAdvance())->toBeFalse();

        $componente->call('selectSubspecies', 'Drow');

        expect($componente->instance()->canAdvance())->toBeTrue();
    });

    it('per una razza che non ne ha, non chiede niente', function () {
        $this->seed(SubraceSeeder::class);

        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('step', 2)
            ->call('selectSpecies', 'Tiefling');

        expect($componente->instance()->canAdvance())->toBeTrue();
    });

    // Appartiene alla razza di prima: tenerla sarebbe un drow mezzorco.
    it('cambiando razza la scelta si azzera', function () {
        $this->seed(SubraceSeeder::class);

        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->call('selectSpecies', 'Elfo')
            ->call('selectSubspecies', 'Drow')
            ->call('selectSpecies', 'Nano');

        expect($componente->get('subspecies'))->toBe('');
    });

    it('la chiama col nome giusto a seconda della razza', function (string $razza, string $atteso) {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->call('selectSpecies', $razza);

        expect($componente->instance()->subspeciesLabel())->toBe($atteso);
    })->with([
        'elfo' => ['Elfo', 'Sottorazza'],
        'dragonide' => ['Dragonide', 'Discendenza draconica'],
        'umano' => ['Umano', 'Etnia'],
    ]);
});

describe('il pannello', function () {
    it('lo aprono DM e admin', function (string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get(SubraceResource::getUrl('index'))
            ->assertOk();
    })->with(['dm', 'admin']);

    it('e resta chiuso ai giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(SubraceResource::getUrl('index'))
            ->assertForbidden();
    });

    it('quelle del manuale non si cancellano', function () {
        $daManuale = Subrace::factory()->fromTheBook()->create();
        $homebrew = Subrace::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ListSubraces::class)
            ->assertTableActionHidden('delete', $daManuale)
            ->assertTableActionVisible('delete', $homebrew);
    });
});
