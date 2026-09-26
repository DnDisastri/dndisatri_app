<?php

declare(strict_types=1);

use App\Actions\Characters\CreateCharacter;
use App\Domain\Dnd\Ability;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\User;

function creaGuerriero(User $owner, array $overrides = []): Character
{
    return app(CreateCharacter::class)->handle(...[
        'owner' => $owner,
        'name' => 'Kaeleth',
        'class' => 'Guerriero',
        'species' => 'Mezzorco',
        'background' => 'Soldato',
        'boughtScores' => ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8],
        'skills' => ['athletics', 'perception'],
        ...$overrides,
    ]);
}

describe('un personaggio appena creato', function () {
    it('parte sempre dal primo livello', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->level)->toBe(1)
            ->and($character->hit_die)->toBe(10);
    });

    it('somma i bonus di specie ai punteggi comprati', function () {
        $character = creaGuerriero(User::factory()->player()->create());


        expect($character->str)->toBe(17)
            ->and($character->con)->toBe(15)
            ->and($character->dex)->toBe(14);
    });

    it('prende il dado vita pieno, non la media', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->hp_max)->toBe(12)
            ->and($character->hp_current)->toBe(12);
    });

    it('ha i tiri salvezza della classe', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->saving_throws)->toBe(['str' => true, 'con' => true])
            ->and($character->savingThrow(Ability::Str))->toBe(5); 
    });

    it('unisce le abilità della classe a quelle del background', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->skills)->toHaveKeys(['athletics', 'perception', 'intimidation'])
            ->and($character->skills['athletics'])->toBe('proficient');
    });

    it('prende velocità e tratti dalla specie, e l\'oro dal background', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect((float) $character->speed)->toBe(9.0)
            ->and($character->species_traits)->toContain('Forza')
            ->and($character->gp)->toBe((int) config('dnd.backgrounds.list.Soldato.gp'));
    });
});

describe('equipaggiamento iniziale', function () {
    it('arriva da classe e background', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->items()->count())->toBeGreaterThan(0)
            ->and($character->ownsItem('Cotta di Maglia'))->toBeTrue();
    });

    it('si indossa da solo, così la classe armatura è giusta subito', function () {

        $character = creaGuerriero(User::factory()->player()->create());
        $character->load('items', 'itemEffects');

        expect($character->equipped(EquipmentSlot::Armor)->name)->toBe('Cotta di Maglia')
            ->and($character->equipped(EquipmentSlot::Shield)->name)->toBe('Scudo')
            ->and($character->equipped(EquipmentSlot::Weapon)->name)->toBe('Spada Lunga')
            ->and($character->armorClass())->toBe(18); 
    });
});

describe('gli incantesimi', function () {
    it('si salvano col loro livello, trucchetti compresi', function () {
        $character = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Elandra',
            class: 'Mago',
            species: 'Elfo',
            background: 'Accolito',
            boughtScores: ['str' => 8, 'dex' => 14, 'con' => 14, 'int' => 15, 'wis' => 12, 'cha' => 8],
            skills: ['arcana', 'history'],
            spells: ['Dardo di Fuoco', 'Mano Magica', 'Dardo Incantato', 'Scudo'],
        );

        $spells = $character->spells()->pluck('level', 'name');

        expect($spells['Dardo di Fuoco'])->toBe(0)
            ->and($spells['Dardo Incantato'])->toBe(1)
            ->and($character->spell_ability)->toBe('int')
            ->and($character->spellSaveDc())->toBe(12);
    });

    it('chi non lancia non ne ha, e non ha caratteristica da incantatore', function () {
        $character = creaGuerriero(User::factory()->player()->create());

        expect($character->spells()->count())->toBe(0)
            ->and($character->spell_ability)->toBeNull();
    });
});
// CreateCharacter rivalida gli input anche quando arrivano da percorsi che possono saltare i controlli del wizard.
describe('quello che la creazione rifiuta', function () {
    it('punteggi che sforano il point buy', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), [
            'boughtScores' => ['str' => 15, 'dex' => 15, 'con' => 15, 'int' => 15, 'wis' => 15, 'cha' => 15],
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('un numero sbagliato di abilità', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), [
            'skills' => ['athletics'],
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('un\'abilità che la classe non può scegliere', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), [
            'skills' => ['athletics', 'arcana'],
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('la stessa abilità due volte', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), [
            'skills' => ['athletics', 'athletics'],
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('classe, specie o background inventati', function () {
        $owner = User::factory()->player()->create();

        expect(fn () => creaGuerriero($owner, ['class' => 'Pasticciere']))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => creaGuerriero($owner, ['species' => 'Marziano']))
            ->toThrow(InvalidArgumentException::class);
    });

    it('un incantesimo oltre il primo livello: si crea sempre a livello 1', function () {
        expect(fn () => app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Elandra',
            class: 'Mago',
            species: 'Elfo',
            background: 'Accolito',
            boughtScores: ['str' => 8, 'dex' => 14, 'con' => 14, 'int' => 15, 'wis' => 12, 'cha' => 8],
            skills: ['arcana', 'history'],
            spells: ['Palla di Fuoco'],
        ))->toThrow(InvalidArgumentException::class);
    });

    it('un incantesimo che non è della classe', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), [
            'spells' => ['Dardo Incantato'],
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('un personaggio senza nome', function () {
        expect(fn () => creaGuerriero(User::factory()->player()->create(), ['name' => '   ']))
            ->toThrow(InvalidArgumentException::class);
    });
});
