<?php

declare(strict_types=1);

use App\Models\Build;
use App\Models\User;


describe('quelle che vengono da prima', function () {
    it('ci sono tutte e otto, già pubblicate', function () {

        expect(Build::count())->toBe(count(config('dnd.builds')))
            ->and(Build::published()->count())->toBe(count(config('dnd.builds')));
    });

    it('hanno classe, sottoclasse e il consiglio a parole', function () {
        $guerriero = Build::where('slug', 'guerriero-campione')->firstOrFail();

        expect($guerriero->title)->toBe('Guerriero Campione')
            ->and($guerriero->class)->toBe('Guerriero')
            ->and($guerriero->subclass)->toBe('Campione')
            ->and($guerriero->abilities_advice)->toBe('FOR e COS')
            ->and($guerriero->tag)->toBe('Semplice · Robusto');
    });

    it('ma non sono complete: i dettagli del 1° non li hanno mai avuti', function () {
        $guerriero = Build::where('slug', 'guerriero-campione')->firstOrFail();

        expect($guerriero->isComplete())->toBeFalse()
            ->and($guerriero->species)->toBeNull()
            ->and($guerriero->scores)->toBeNull();
    });
});

describe('quanto è completa', function () {
    it('lo è quando specie, background, punteggi e abilità ci sono tutti', function () {
        expect(Build::factory()->complete()->create()->isComplete())->toBeTrue();
    });

    it('e non lo è se ne manca anche uno solo', function () {
        expect(Build::factory()->complete()->create(['scores' => null])->isComplete())->toBeFalse()
            ->and(Build::factory()->complete()->create(['species' => null])->isComplete())->toBeFalse();
    });
});

describe('il passaggio alla creazione guidata', function () {
    it('porta tutto quello che la build sa', function () {
        $build = Build::factory()->complete()->create();

        $state = $build->wizardState();

        expect($state['class'])->toBe('Guerriero')
            ->and($state['species'])->toBe('Nano')
            ->and($state['background'])->toBe('Soldato')
            ->and($state['scores']['str'])->toBe(15)
            ->and($state['skills'])->toBe(['athletics', 'perception']);
    });

    it('e non porta caselle vuote, che cancellerebbero i valori di partenza', function () {
// Una build incompleta non deve sovrascrivere con valori vuoti quelli già inizializzati dal wizard.
        $state = Build::factory()->create()->wizardState();

        expect($state)->toHaveKey('class')
            ->and($state)->not->toHaveKey('scores')
            ->and($state)->not->toHaveKey('species');
    });
});

describe('chi le scrive', function () {
    it('un DM o un admin, non un giocatore', function () {
        expect(User::factory()->dm()->create()->can('create', Build::class))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('create', Build::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', Build::class))->toBeFalse();
    });

    it('un DM modifica le proprie', function () {
        $dm = User::factory()->dm()->create();

        expect($dm->can('update', Build::factory()->writtenBy($dm)->create()))->toBeTrue();
    });

    it('non quelle di un altro DM', function () {
        $dm = User::factory()->dm()->create();
        $altrui = Build::factory()->writtenBy(User::factory()->dm()->create())->create();

        expect($dm->can('update', $altrui))->toBeFalse();
    });

    it('ma sì quelle senza autore: le otto ereditate sono del gruppo', function () {
// Le build migrate senza autore sono condivise e devono restare modificabili dai DM.
        $ereditata = Build::where('slug', 'guerriero-campione')->firstOrFail();

        expect(User::factory()->dm()->create()->can('update', $ereditata))->toBeTrue();
    });

    it('e nessuno le cancella, tranne un admin', function () {
        $build = Build::factory()->create();

        expect(User::factory()->dm()->create()->can('delete', $build))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('delete', $build))->toBeTrue();
    });
});

describe('le bozze', function () {
    it('non compaiono fra le pubblicate', function () {
        Build::factory()->draft()->create();

        expect(Build::published()->count())->toBe(count(config('dnd.builds')));
    });

    it('e le vede solo chi può metterci mano', function () {
        $dm = User::factory()->dm()->create();
        $bozza = Build::factory()->draft()->writtenBy($dm)->create();

        expect($dm->can('view', $bozza))->toBeTrue()
            ->and(User::factory()->player()->create()->can('view', $bozza))->toBeFalse();
    });
});
