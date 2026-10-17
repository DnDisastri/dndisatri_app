<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\CreateCharacter;
use App\Actions\Characters\RequestLevelUp;
use App\Enums\RulesEdition;
use App\Filament\Resources\Feats\FeatResource;
use App\Filament\Resources\Feats\Pages\CreateFeat;
use App\Filament\Resources\Feats\Pages\ListFeats;
use App\Livewire\CharacterWizard;
use App\Models\Character;
use App\Models\Feat;
use App\Models\User;
use Database\Seeders\FeatSeeder;
use Livewire\Livewire;

function creaDiSpecie(string $species, array $overrides = []): Character
{
    return app(CreateCharacter::class)->handle(...[
        'owner' => User::factory()->player()->create(),
        'name' => 'Arwen',
        'class' => 'Guerriero',
        'species' => $species,
        'background' => 'Soldato',
        'boughtScores' => ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8],
        'skills' => ['acrobatics', 'perception'],
        'speciesChoices' => ['dex', 'con'],
        ...$overrides,
    ]);
}

describe('edizione delle regole', function () {
    it('i personaggi nascono con le regole 2014', function () {
        $character = creaDiSpecie('Mezzelfo', ['speciesSkills' => ['insight', 'persuasion']]);

        expect($character->fresh()->rules_edition)->toBe(RulesEdition::Classic);
    });

    it('le regole 2024 non si possono ancora scegliere', function () {
        expect(fn () => creaDiSpecie('Mezzelfo', [
            'speciesSkills' => ['insight', 'persuasion'],
            'edition' => RulesEdition::Revised,
        ]))->toThrow(InvalidArgumentException::class, 'non sono ancora disponibili');
    });

    it('la scheda mostra con quali regole è fatto', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->get(route('characters.show', $character))
            ->assertSee('regole 5e (2014)');
    });
});

describe('Mezzelfo e Umano Variante', function () {
    it('il Mezzelfo sceglie due abilità', function () {
        $character = creaDiSpecie('Mezzelfo', ['speciesSkills' => ['insight', 'persuasion']]);

        expect($character->skills)->toHaveKeys(['insight', 'persuasion', 'acrobatics']);
    });

    it('senza le due abilità il Mezzelfo non nasce', function () {
        expect(fn () => creaDiSpecie('Mezzelfo', ['speciesSkills' => ['insight', 'insight']]))
            ->toThrow(InvalidArgumentException::class, 'vuole 2 abilità');
    });

    it('le altre specie non ne scelgono', function () {
        expect(fn () => creaDiSpecie('Mezzorco', ['speciesSkills' => ['insight'], 'speciesChoices' => []]))
            ->toThrow(InvalidArgumentException::class, 'non fa scegliere abilità');
    });

    it("l'Umano Variante prende un'abilità e un talento dal catalogo", function () {
        $feat = Feat::create(['name' => 'Allerta', 'description' => '+5 all\'iniziativa.']);

        $character = creaDiSpecie('Umano (Variante)', ['speciesSkills' => ['stealth'], 'featId' => $feat->id]);

        expect($character->skills)->toHaveKey('stealth')
            ->and($character->feats()->first())
            ->name->toBe('Allerta')
            ->level->toBe(1)
            ->source->toBe('species');
    });

    it('ma non un talento fuori dal catalogo', function () {
        $altreRegole = Feat::create(['name' => 'Allerta', 'rules_edition' => '2024']);

        expect(fn () => creaDiSpecie('Umano (Variante)', ['speciesSkills' => ['stealth'], 'featId' => $altreRegole->id]))
            ->toThrow(InvalidArgumentException::class, 'Talento non trovato');
    });

    it('ma senza talento no', function () {
        expect(fn () => creaDiSpecie('Umano (Variante)', ['speciesSkills' => ['stealth']]))
            ->toThrow(InvalidArgumentException::class, 'sceglie un talento');
    });

    it('nel passo 5 le abilità di specie sono già prese', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->set('class', 'Guerriero')
            ->set('species', 'Mezzelfo')
            ->set('speciesSkills', ['athletics', 'insight'])
            ->set('step', 5)
            ->assertSee('dalla specie');
    });
});

describe('passaggio di livello', function () {
    beforeEach(function () {
        $this->guerriero = Character::factory()->create([
            'class' => 'Guerriero', 'level' => 3, 'con' => 14, 'hp_max' => 28, 'hp_current' => 28,
        ]);
    });

    it('i PF si possono tirare al tavolo', function () {
        $change = app(RequestLevelUp::class)->handle($this->guerriero, $this->guerriero->user, hpRoll: 3);

        expect($change->diff['hp_max'])->toBe(28 + 3 + 2)
            ->and($change->summary)->toContain('+5 PF (d10, tirato 3).');
    });

    it('il tiro sta fra 1 e il dado vita', function () {
        expect(fn () => app(RequestLevelUp::class)->handle($this->guerriero, $this->guerriero->user, hpRoll: 11))
            ->toThrow(InvalidArgumentException::class, 'va da 1 a 10');
    });

    it('il modulo manda il tiro', function () {
        $this->actingAs($this->guerriero->user)
            ->post(route('proposals.level-up', $this->guerriero), ['hp_method' => 'tiro', 'hp_roll' => 7])
            ->assertRedirect();

        expect($this->guerriero->pendingChanges()->first()->summary)->toContain('tirato 7');
    });

    it('il talento si sceglie dal menu, col prerequisito per il DM', function () {
        $feat = Feat::create(['name' => 'Lottatore', 'prerequisite' => 'Forza 13 o superiore', 'description' => 'Afferra.']);

        $change = app(RequestLevelUp::class)->handle(
            $this->guerriero, $this->guerriero->user, asiMode: 'feat', featId: $feat->id,
        );

        expect($change->diff['feat'])->toBe(['name' => 'Lottatore', 'description' => 'Afferra.'])
            ->and($change->summary)->toContain('Talento: Lottatore (prerequisito: Forza 13 o superiore).');
    });

    it('un talento di altre regole non si prende', function () {
        $feat = Feat::create(['name' => 'Lottatore', 'rules_edition' => '2024']);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $this->guerriero, $this->guerriero->user, asiMode: 'feat', featId: $feat->id,
        ))->toThrow(InvalidArgumentException::class, 'Talento non trovato');
    });

    it('il nome scritto a mano non vale: serve il menu', function () {
        $this->actingAs($this->guerriero->user)
            ->post(route('proposals.level-up', $this->guerriero), ['asi_mode' => 'feat', 'feat_name' => 'Inventato'])
            ->assertSessionHasErrors(['proposta' => "Scegli il talento dall'elenco."]);

        expect($this->guerriero->pendingChanges()->exists())->toBeFalse();
    });

    it('il modulo elenca i talenti del catalogo', function () {
        Feat::create(['name' => 'Lottatore']);

        $this->actingAs($this->guerriero->user)
            ->get(route('proposals.level-up', $this->guerriero))
            ->assertSee('Lottatore')
            ->assertSee('Tirato al tavolo');
    });
});

describe('sostituire un incantesimo', function () {
    beforeEach(function () {
        $this->bardo = Character::factory()->create(['class' => 'Bardo', 'level' => 3]);
        $this->bardo->spells()->create(['name' => 'Charme su Persone', 'level' => 1]);
    });

    it('il Bardo ne dimentica uno e ne impara un altro', function () {
        $change = app(RequestLevelUp::class)->handle(
            $this->bardo, $this->bardo->user, forget: 'Charme su Persone', replaceWith: 'Sonno',
        );

        expect($change->diff['swap'])->toBe(['forget' => 'Charme su Persone', 'learn' => 'Sonno']);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($this->bardo->spells()->pluck('name')->all())->toBe(['Sonno']);
    });

    it('un Mago no', function () {
        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3]);
        $mago->spells()->create(['name' => 'Sonno', 'level' => 1]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $mago, $mago->user, forget: 'Sonno', replaceWith: 'Dardo Incantato',
        ))->toThrow(InvalidArgumentException::class, 'non sostituisce');
    });

    it('solo un incantesimo che conosce', function () {
        expect(fn () => app(RequestLevelUp::class)->handle(
            $this->bardo, $this->bardo->user, forget: 'Sonno', replaceWith: 'Sonno',
        ))->toThrow(InvalidArgumentException::class, 'che conosci');
    });

    it('il modulo propone la sostituzione al Bardo', function () {
        $this->actingAs($this->bardo->user)
            ->get(route('proposals.level-up', $this->bardo))
            ->assertSee('Sostituisci un incantesimo')
            ->assertSee('Dimentico Charme su Persone');
    });
});

describe('il catalogo dei talenti nel pannello', function () {
    it('lo aprono DM e admin', function (string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get(FeatResource::getUrl('index'))
            ->assertOk();
    })->with(['dm', 'admin']);

    it('non i giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(FeatResource::getUrl('index'))
            ->assertForbidden();
    });

    it('il seeder porta i talenti del manuale, che non si cancellano', function () {
        $this->seed(FeatSeeder::class);
        $homebrew = Feat::create(['name' => 'Talento della casa']);

        expect(Feat::where('is_homebrew', false)->count())->toBe(42);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ListFeats::class)
            ->assertTableActionHidden('delete', Feat::firstWhere('name', 'Lottatore'))
            ->assertTableActionVisible('delete', $homebrew);
    });

    it('lo stesso nome si ripete solo con altre regole', function () {
        Feat::create(['name' => 'Allerta']);

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(CreateFeat::class)
            ->fillForm(['name' => 'Allerta', 'rules_edition' => '2014'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(CreateFeat::class)
            ->fillForm(['name' => 'Allerta', 'rules_edition' => '2024'])
            ->call('create')
            ->assertHasNoFormErrors();
    });
});
