<?php

declare(strict_types=1);

use App\Actions\Characters\CreateCharacter;
use App\Domain\Dnd\BackgroundRules;
use App\Domain\Dnd\SpeciesRules;
use App\Domain\Dnd\SubraceCatalogue;
use App\Enums\RulesEdition;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Backgrounds\BackgroundResource;
use App\Livewire\CharacterWizard;
use App\Models\AppSetting;
use App\Models\Character;
use App\Models\Feat;
use App\Models\User;
use Database\Seeders\SubraceSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

function crea2024(User $owner, array $overrides = []): Character
{
    return app(CreateCharacter::class)->handle(...[
        'owner' => $owner,
        'edition' => RulesEdition::Revised,
        'name' => 'Brann',
        'class' => 'Guerriero',
        'species' => 'Goliath',
        'subspecies' => 'Gigante del Fuoco',
        'background' => 'Soldato',
        'backgroundBonuses' => ['str' => 2, 'con' => 1],
        'boughtScores' => ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8],
        'skills' => ['acrobatics', 'perception'],
        ...$overrides,
    ]);
}

describe('i dati 2024', function () {
    it('arrivano con la migrazione accanto a quelli 2014', function () {
        expect(SpeciesRules::names(RulesEdition::Revised))->toHaveCount(9)
            ->and(SpeciesRules::names(RulesEdition::Revised))->toContain('Goliath', 'Orco')->not->toContain('Mezzelfo')
            ->and(SpeciesRules::names())->toContain('Mezzelfo')
            ->and(BackgroundRules::names(RulesEdition::Revised))->toBe(['Accolito', 'Criminale', 'Saggio', 'Soldato'])
            ->and(BackgroundRules::of('Soldato', RulesEdition::Revised)->origin_feat)->toBe('Attaccante selvaggio')
            ->and(Feat::where('rules_edition', '2024')->where('category', Feat::ORIGIN)->count())->toBe(6);
    });

    it("l'elfo 2024 ha i lignaggi, quello 2014 le sue sottorazze", function () {
        $this->seed(SubraceSeeder::class);

        expect(app(SubraceCatalogue::class)->of('Elfo', RulesEdition::Revised)->pluck('name')->all())->toBe(['Drow', 'Elfo Alto', 'Elfo Silvano'])
            ->and(app(SubraceCatalogue::class)->of('Elfo')->pluck('name')->all())->toContain('Elfo dei Boschi');
    });
});

describe('chi crea in 2024', function () {
    it('i giocatori no, finché un admin non le apre', function () {
        $giocatore = User::factory()->player()->create();

        expect(fn () => crea2024($giocatore))->toThrow(InvalidArgumentException::class, 'non sono ancora aperte');

        AppSetting::put(AppSetting::RULES_2024_OPEN, true);

        expect(crea2024($giocatore)->rules_edition)->toBe(RulesEdition::Revised);
    });

    it('i DM sempre, per provarle', function () {
        expect(crea2024(User::factory()->dm()->create())->rules_edition)->toBe(RulesEdition::Revised);
    });
});

describe('la creazione 2024', function () {
    beforeEach(fn () => AppSetting::put(AppSetting::RULES_2024_OPEN, true));

    it('bonus e talento di origine dal background, niente bonus dalla specie', function () {
        $brann = crea2024(User::factory()->player()->create());

        expect($brann->str)->toBe(17)
            ->and($brann->con)->toBe(15)
            ->and($brann->dex)->toBe(14)
            ->and($brann->speed)->toBe(10.5)
            ->and($brann->species_traits)->toContain('1d10 danni da fuoco')
            ->and($brann->feats()->where('source', 'background')->value('name'))->toBe('Attaccante selvaggio')
            ->and($brann->skills)->toHaveKeys(['athletics', 'intimidation']);
    });

    it('anche +1 a tutte e tre', function () {
        $brann = crea2024(User::factory()->player()->create(), ['backgroundBonuses' => ['str' => 1, 'dex' => 1, 'con' => 1]]);

        expect([$brann->str, $brann->dex, $brann->con])->toBe([16, 15, 15]);
    });

    it('solo sulle caratteristiche del background, e solo +2/+1 o +1/+1/+1', function () {
        $giocatore = User::factory()->player()->create();

        expect(fn () => crea2024($giocatore, ['backgroundBonuses' => ['cha' => 2, 'str' => 1]]))
            ->toThrow(InvalidArgumentException::class, 'non aumenta questa caratteristica')
            ->and(fn () => crea2024($giocatore, ['backgroundBonuses' => ['str' => 2, 'con' => 2]]))
            ->toThrow(InvalidArgumentException::class, '+2 e +1');
    });

    it('nel 2014 il background non dà bonus', function () {
        expect(fn () => app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(), name: 'Vecchio', class: 'Guerriero', species: 'Mezzorco',
            background: 'Soldato', boughtScores: ['str' => 15, 'dex' => 14, 'con' => 14, 'int' => 8, 'wis' => 10, 'cha' => 8],
            skills: ['acrobatics', 'perception'], backgroundBonuses: ['str' => 2, 'con' => 1],
        ))->toThrow(InvalidArgumentException::class, 'non aumenta le caratteristiche');
    });

    it("l'Umano 2024 sceglie un talento di origine, non uno generale", function () {
        $giocatore = User::factory()->player()->create();
        $base = ['species' => 'Umano', 'subspecies' => null, 'speciesSkills' => ['stealth']];

        $allerta = Feat::where('rules_edition', '2024')->firstWhere('name', 'Allerta');
        $lottatore = Feat::where('rules_edition', '2024')->firstWhere('name', 'Lottatore');

        expect(crea2024($giocatore, [...$base, 'featId' => $allerta->id])->feats()->where('source', 'species')->value('name'))->toBe('Allerta')
            ->and(fn () => crea2024(User::factory()->player()->create(), [...$base, 'featId' => $lottatore->id]))
            ->toThrow(InvalidArgumentException::class, 'talento di origine');
    });
});

describe('la procedura guidata', function () {
    it('al giocatore il 2024 resta «In arrivo»', function () {
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(CharacterWizard::class)
            ->assertSee('In arrivo')
            ->call('selectEdition', '2024')
            ->assertSet('rulesEdition', '2014');
    });

    it('il DM lo sceglie e vede specie e background 2024', function () {
        $this->actingAs(User::factory()->dm()->create());

        Livewire::test(CharacterWizard::class)
            ->assertSee('In prova, solo per i DM')
            ->call('selectEdition', '2024')
            ->assertSet('rulesEdition', '2024')
            ->set('step', 2)
            ->assertSee('Goliath')
            ->assertDontSee('Mezzelfo')
            ->set('step', 4)
            ->call('selectBackground', 'Soldato')
            ->assertSee('Talento di origine')
            ->assertSee('Attaccante selvaggio')
            ->set('bonusPlus2', 'str')
            ->set('bonusPlus1', 'con')
            ->assertSet('step', 4)
            ->tap(fn ($c) => expect($c->instance()->finalScores()['str'])->toBe(10)
                ->and($c->instance()->canAdvance())->toBeTrue());
    });
});

describe('il pannello', function () {
    it('le Impostazioni sono solo degli admin, e aprono il 2024 ai giocatori', function () {
        $this->actingAs(User::factory()->dm()->create())->get(Settings::getUrl())->assertForbidden();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Settings::class)
            ->assertSee('Chiuse')
            ->callAction(TestAction::make('regole2024')->schemaComponent('regole2024', schema: 'content'));

        expect(AppSetting::get(AppSetting::RULES_2024_OPEN))->toBeTrue();
    });

    it('i background si aprono per DM e admin', function () {
        $this->actingAs(User::factory()->dm()->create())->get(BackgroundResource::getUrl('index'))->assertOk();
        $this->actingAs(User::factory()->player()->create())->get(BackgroundResource::getUrl('index'))->assertForbidden();
    });
});
