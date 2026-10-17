<?php

declare(strict_types=1);

use App\Actions\Characters\CreateCharacter;
use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\ClassCatalogue;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Multiclass;
use App\Domain\Dnd\PointBuy;
use App\Domain\Dnd\SpeciesRules;
use App\Domain\Dnd\SpellSlots;
use App\Filament\Resources\GameClasses\GameClassResource;
use App\Filament\Resources\GameClasses\Pages\EditGameClass;
use App\Filament\Resources\GameClasses\Pages\ListGameClasses;
use App\Filament\Resources\Species\Pages\EditSpecies;
use App\Filament\Resources\Species\SpeciesResource;
use App\Livewire\CharacterWizard;
use App\Models\Character;
use App\Models\GameClass;
use App\Models\Species;
use App\Models\Subclass;
use App\Models\User;
use Livewire\Livewire;

function creaCon(string $class, string $species = 'Mezzorco', array $overrides = []): Character
{
    return app(CreateCharacter::class)->handle(...[
        'owner' => User::factory()->player()->create(),
        'name' => 'Prova',
        'class' => $class,
        'species' => $species,
        'background' => 'Soldato',
        'boughtScores' => ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8],
        'skills' => array_slice(ClassRules::skillChoices($class), 0, ClassRules::skillCount($class)),
        ...$overrides,
    ]);
}

describe('le specie', function () {
    it('arrivano con la migrazione', function () {
        expect(SpeciesRules::names())->toHaveCount(10)
            ->and(SpeciesRules::speed('Nano'))->toBe(7.5)
            ->and(SpeciesRules::freeBonuses('Mezzelfo'))->toBe(2);
    });

    it('una specie nuova dal pannello si sceglie in creazione', function () {
        Species::create(['name' => 'Goliath', 'asi' => ['str' => 2, 'con' => 1], 'speed' => 9, 'traits' => 'Resistenza della montagna.']);

        $this->actingAs(User::factory()->player()->create());
        Livewire::test(CharacterWizard::class)->set('step', 2)->assertSee('Goliath');

        $goliath = creaCon('Guerriero', 'Goliath');

        expect($goliath->str)->toBe(17)
            ->and($goliath->species_traits)->toContain('Resistenza della montagna');
    });

    it('i bonus cambiati dal pannello cambiano il point buy', function () {
        Species::firstWhere('name', 'Mezzorco')->update(['asi' => ['str' => 1]]);

        expect(PointBuy::withSpecies(PointBuy::starting(), 'Mezzorco')['str'])->toBe(9);
    });

    it('quella che un personaggio usa non si cancella', function () {
        Character::factory()->create(['race' => 'Tiefling']);

        expect(User::factory()->admin()->create()->can('delete', Species::firstWhere('name', 'Tiefling')))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('delete', Species::create(['name' => 'Kenku'])))->toBeTrue();
    });

    it('il nome non si cambia dal pannello', function () {
        $nano = Species::firstWhere('name', 'Nano');

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(EditSpecies::class, ['record' => $nano->getRouteKey()])
            ->assertFormFieldIsDisabled('name');
    });
});

describe('le classi', function () {
    it('arrivano con la migrazione, con i loro numeri', function () {
        expect(ClassRules::hitDie('Barbaro'))->toBe(12)
            ->and(ClassRules::savingThrows('Mago'))->toBe(['int', 'wis'])
            ->and(ClassRules::prepares('Paladino', 1))->toBeFalse()
            ->and(ClassRules::prepares('Paladino', 2))->toBeTrue()
            ->and(ClassRules::swapsSpells('Bardo'))->toBeTrue()
            ->and(Multiclass::skillsOnEntry('Ranger')['count'])->toBe(1);
    });

    it('il dado vita cambiato dal pannello vale subito', function () {
        GameClass::firstWhere('name', 'Mago')->update(['hit_die' => 8]);

        expect(creaCon('Mago')->hit_die)->toBe(8);
    });

    it('salvare dal pannello non rovina l\'equipaggiamento iniziale', function () {
        $prima = ClassRules::startingEquipment('Barbaro');

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditGameClass::class, ['record' => GameClass::firstWhere('name', 'Barbaro')->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        app()->forgetInstance(ClassCatalogue::class);

        // Il pannello aggiunge la quantità vuota, che vale 1: contano scelte, alternative e nomi.
        $nomi = fn (array $equip) => collect($equip['choices'])->map(fn ($c) => [$c['label'], collect($c['options'])->map(fn ($o) => [$o['label'], collect($o['items'])->pluck('name')->all()])->all()])->all();

        expect($nomi(ClassRules::startingEquipment('Barbaro')))->toBe($nomi($prima))
            ->and(GameClass::firstWhere('name', 'Barbaro')->multiclass_requirements)->toBe([['abilities' => ['str']]]);

        $barbaro = creaCon('Barbaro', overrides: ['equipmentChoices' => [1]]);

        expect($barbaro->items()->where('name', 'Spada Lunga')->exists())->toBeTrue();
    });

    it('quella che qualcuno usa non si cancella', function () {
        Character::factory()->create(['class' => 'Monaco']);
        $nuova = GameClass::create(['name' => 'Cacciatore di sangue', 'hit_die' => 10, 'saves' => ['str', 'wis'], 'skill_choices' => ['athletics']]);

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(ListGameClasses::class)
            ->assertTableActionHidden('delete', GameClass::firstWhere('name', 'Monaco'))
            ->assertTableActionVisible('delete', $nuova);
    });
});

describe("l'Artefice", function () {
    it('è a catalogo con le sue sottoclassi', function () {
        expect(ClassRules::exists('Artefice'))->toBeTrue()
            ->and(ClassRules::hitDie('Artefice'))->toBe(8)
            ->and(ClassRules::savingThrows('Artefice'))->toBe(['con', 'int'])
            ->and(Subclass::where('class', 'Artefice')->count())->toBe(4);
    });

    it('mezzo incantatore per eccesso: ha slot dal primo livello', function () {
        expect(CasterType::for('Artefice'))->toBe(CasterType::HalfUp)
            ->and(SpellSlots::for(CasterType::HalfUp, 1)->countAt(1))->toBe(2)
            ->and(SpellSlots::for(CasterType::HalfUp, 3)->countAt(1))->toBe(3)
            ->and(SpellSlots::for(CasterType::HalfUp, 5)->countAt(2))->toBe(2)
            ->and(Multiclass::casterLevel(['Artefice' => 3, 'Mago' => 2]))->toBe(4);
    });

    it('prepara con Intelligenza più metà livello', function () {
        expect(ClassRules::preparationLimit('Artefice', 5, 3))->toBe(5)
            ->and(ClassRules::preparationLimit('Artefice', 1, 3))->toBe(3);
    });

    it('si crea con i suoi incantesimi e il suo equipaggiamento', function () {
        $artefice = creaCon('Artefice', overrides: [
            'boughtScores' => ['str' => 8, 'dex' => 14, 'con' => 14, 'int' => 15, 'wis' => 10, 'cha' => 10],
            'spells' => ['Mano Magica', 'Guida', 'Cura Ferite'],
        ]);

        expect($artefice->spells()->count())->toBe(3)
            ->and($artefice->items()->where('name', 'Balestra Leggera')->exists())->toBeTrue()
            ->and($artefice->spellSlots()->countAt(1))->toBe(2);
    });
});

describe('le pagine del pannello', function () {
    it('le aprono DM e admin', function (string $risorsa, string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get($risorsa::getUrl('index'))
            ->assertOk();
    })->with(['specie' => SpeciesResource::class, 'classi' => GameClassResource::class])->with(['dm', 'admin']);

    it('non i giocatori', function (string $risorsa) {
        $this->actingAs(User::factory()->player()->create())
            ->get($risorsa::getUrl('index'))
            ->assertForbidden();
    })->with(['specie' => SpeciesResource::class, 'classi' => GameClassResource::class]);

    it('la scheda di una classe si apre', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(GameClassResource::getUrl('edit', ['record' => GameClass::firstWhere('name', 'Mago')]))
            ->assertOk()
            ->assertSee('Lista degli incantesimi');
    });
});
