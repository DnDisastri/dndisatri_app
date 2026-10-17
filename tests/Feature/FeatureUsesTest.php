<?php

declare(strict_types=1);

use App\Actions\Characters\TakeRest;
use App\Actions\Characters\UseFeature;
use App\Domain\Dnd\Features;
use App\Enums\FeatureUsesFormula;
use App\Enums\RestType;
use App\Filament\Resources\ClassFeatures\ClassFeatureResource;
use App\Filament\Resources\ClassFeatures\Pages\EditClassFeature;
use App\Livewire\FeatureTracker;
use App\Livewire\HitPointTracker;
use App\Models\Character;
use App\Models\ClassFeature;
use App\Models\User;
use Livewire\Livewire;

function privilegio(string $classe, string $nome): ClassFeature
{
    return ClassFeature::where('owner', $classe)->where('name', $nome)->firstOrFail();
}

function di(Character $character, string $nome): array
{
    return Features::for($character->fresh())->firstWhere('nome', $nome);
}

describe('i numeri ricavati dalle frasi', function () {
    it('riconosce le frasi ricorrenti', function () {
        expect(privilegio('Guerriero', 'Recuperare energie'))
            ->uses_formula->toBe(FeatureUsesFormula::Fixed)
            ->uses_value->toBe(1)
            ->recharge->toBe(RestType::Short)
            ->and(privilegio('Monaco', 'Raffica di colpi'))
            ->spends->toBe('Punti ki')
            ->spends_amount->toBe(1)
            ->and(privilegio('Chierico', 'Scacciare non morti')->spends)->toBe('Incanalare divinità');
    });

    it('e i privilegi principali hanno i loro numeri', function () {
        expect(privilegio('Barbaro', 'Ira'))
            ->uses_formula->toBe(FeatureUsesFormula::Table)
            ->can_activate->toBeTrue()
            ->and(privilegio('Bardo', 'Ispirazione bardica')->short_rest_from_level)->toBe(5);
    });
});

describe('quanti usi', function () {
    it('l\'Ira cresce col livello e al 20º non ha limite', function () {
        expect(di(Character::factory()->create(['class' => 'Barbaro', 'level' => 3]), 'Ira')['massimo'])->toBe(3)
            ->and(di(Character::factory()->create(['class' => 'Barbaro', 'level' => 20]), 'Ira')['massimo'])->toBe(Features::UNLIMITED);
    });

    it('dal livello, dalla caratteristica o dal bonus di competenza', function () {
        expect(di(Character::factory()->create(['class' => 'Monaco', 'level' => 5]), 'Punti ki')['massimo'])->toBe(5)
            ->and(di(Character::factory()->create(['class' => 'Paladino', 'level' => 3]), 'Imposizione delle mani')['massimo'])->toBe(15)
            ->and(di(Character::factory()->create(['class' => 'Bardo', 'level' => 1, 'cha' => 16]), 'Ispirazione bardica')['massimo'])->toBe(3)
            ->and(di(Character::factory()->create(['class' => 'Bardo', 'level' => 1, 'cha' => 8]), 'Ispirazione bardica')['massimo'])->toBe(1);
    });
});

describe('usare', function () {
    it('un uso si spende e, finiti, non si spende più', function () {
        $guerriero = Character::factory()->create(['class' => 'Guerriero', 'level' => 1]);
        $id = privilegio('Guerriero', 'Recuperare energie')->id;

        app(UseFeature::class)->use($guerriero, $id);

        expect(di($guerriero, 'Recuperare energie')['usati'])->toBe(1)
            ->and(fn () => app(UseFeature::class)->use($guerriero, $id))->toThrow(RuntimeException::class, 'usi finiti');
    });

    it('chi consuma una riserva spende i suoi punti', function () {
        $monaco = Character::factory()->create(['class' => 'Monaco', 'level' => 5]);

        app(UseFeature::class)->use($monaco, privilegio('Monaco', 'Raffica di colpi')->id);
        app(UseFeature::class)->use($monaco, privilegio('Monaco', 'Colpo stordente')->id);

        expect(di($monaco, 'Punti ki')['usati'])->toBe(2);
    });

    it('un privilegio di un\'altra classe no', function () {
        $guerriero = Character::factory()->create(['class' => 'Guerriero', 'level' => 5]);

        expect(fn () => app(UseFeature::class)->use($guerriero, privilegio('Barbaro', 'Ira')->id))
            ->toThrow(RuntimeException::class, 'non è del personaggio');
    });

    it('l\'Ira si accende consumando un uso, e si spegne', function () {
        $barbaro = Character::factory()->create(['class' => 'Barbaro', 'level' => 1]);
        $ira = privilegio('Barbaro', 'Ira')->id;

        app(UseFeature::class)->toggle($barbaro, $ira);

        expect(di($barbaro, 'Ira'))->attivo->toBeTrue()->usati->toBe(1);

        app(UseFeature::class)->toggle($barbaro, $ira);

        expect(di($barbaro, 'Ira'))->attivo->toBeFalse()->usati->toBe(1);
    });
});

describe('i riposi', function () {
    it('il breve rimette solo quelli del breve', function () {
        $guerriero = Character::factory()->create(['class' => 'Guerriero', 'level' => 9]);
        app(UseFeature::class)->use($guerriero, privilegio('Guerriero', 'Recuperare energie')->id);
        app(UseFeature::class)->use($guerriero, privilegio('Guerriero', 'Indomabile')->id);

        app(TakeRest::class)->handle($guerriero->fresh(), RestType::Short);

        expect(di($guerriero, 'Recuperare energie')['usati'])->toBe(0)
            ->and(di($guerriero, 'Indomabile')['usati'])->toBe(1);
    });

    it('il lungo rimette tutto e spegne gli effetti', function () {
        $barbaro = Character::factory()->create(['class' => 'Barbaro', 'level' => 3]);
        app(UseFeature::class)->toggle($barbaro, privilegio('Barbaro', 'Ira')->id);

        app(TakeRest::class)->handle($barbaro->fresh(), RestType::Long);

        expect(di($barbaro, 'Ira'))->usati->toBe(0)->attivo->toBeFalse();
    });

    it('l\'Ispirazione bardica torna col breve dal 5º livello', function () {
        $bardo = Character::factory()->create(['class' => 'Bardo', 'level' => 5, 'cha' => 16]);
        app(UseFeature::class)->use($bardo, privilegio('Bardo', 'Ispirazione bardica')->id);

        app(TakeRest::class)->handle($bardo->fresh(), RestType::Short);

        expect(di($bardo, 'Ispirazione bardica')['usati'])->toBe(0);
    });

    it('il modale del riposo breve dice cosa torna e cosa si può usare', function () {
        $mago = Character::factory()->create(['class' => 'Mago', 'level' => 3]);
        $this->actingAs($mago->user);

        Livewire::test(HitPointTracker::class, ['character' => $mago])
            ->call('chiediRiposo', 'short')
            ->assertSee('Puoi usare adesso: Recupero arcano');

        $guerriero = Character::factory()->create(['class' => 'Guerriero', 'level' => 2]);
        app(UseFeature::class)->use($guerriero, privilegio('Guerriero', 'Azione impetuosa')->id);
        $this->actingAs($guerriero->user);

        Livewire::test(HitPointTracker::class, ['character' => $guerriero])
            ->call('chiediRiposo', 'short')
            ->assertSee('Tornano: Azione impetuosa (1 speso)');
    });
});

describe('la scheda', function () {
    it('mostra le risorse e si usano con un tocco', function () {
        $monaco = Character::factory()->create(['class' => 'Monaco', 'level' => 2]);
        $this->actingAs($monaco->user);

        Livewire::test(FeatureTracker::class, ['character' => $monaco])
            ->assertSee('Risorse')
            ->assertSee('Punti ki')
            ->assertSee('Raffica di colpi')
            ->call('usa', privilegio('Monaco', 'Raffica di colpi')->id)
            ->assertSee('1');

        expect(di($monaco, 'Punti ki')['usati'])->toBe(1);
    });

    it('un altro giocatore non li tocca', function () {
        $barbaro = Character::factory()->create(['class' => 'Barbaro', 'level' => 1]);
        $this->actingAs(User::factory()->player()->create());

        Livewire::test(FeatureTracker::class, ['character' => $barbaro])
            ->call('attiva', privilegio('Barbaro', 'Ira')->id)
            ->assertForbidden();
    });

    it('l\'Ira accesa si vede col suo effetto', function () {
        $barbaro = Character::factory()->create(['class' => 'Barbaro', 'level' => 1]);
        $this->actingAs($barbaro->user);

        Livewire::test(FeatureTracker::class, ['character' => $barbaro])
            ->call('attiva', privilegio('Barbaro', 'Ira')->id)
            ->assertSee('Ira attiva.')
            ->assertSee('resistenza a danni contundenti');
    });
});

describe('il pannello', function () {
    it('apre l\'elenco a DM e admin, non ai giocatori', function () {
        $this->actingAs(User::factory()->dm()->create())->get(ClassFeatureResource::getUrl('index'))->assertOk();
        $this->actingAs(User::factory()->player()->create())->get(ClassFeatureResource::getUrl('index'))->assertForbidden();
    });

    it('salvare l\'Ira non rovina la sua tabella', function () {
        $ira = privilegio('Barbaro', 'Ira');

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditClassFeature::class, ['record' => $ira->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(di(Character::factory()->create(['class' => 'Barbaro', 'level' => 6]), 'Ira')['massimo'])->toBe(4);
    });
});
