<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\RequestLevelUp;
use App\Models\Character;
use App\Models\User;

// Gli incantesimi appresi con il level-up entrano nella scheda solo dopo l'approvazione della richiesta.
describe('la richiesta', function () {
    it('porta gli incantesimi nel diff e nel riassunto', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Palla di Fuoco', 'Fulmine'],
        );

        expect($change->diff['spells'])->toBe(['Palla di Fuoco', 'Fulmine'])
            ->and($change->summary)->toContain('Impara: Palla di Fuoco, Fulmine.');
    });

    it('scarta i doppioni della stessa richiesta', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Fulmine', 'Fulmine'],
        );

        expect($change->diff['spells'])->toBe(['Fulmine']);
    });
});

describe('cosa non si può imparare', function () {
    it('un incantesimo di un\'altra classe', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Cura Ferite'],
        ))->toThrow(InvalidArgumentException::class, 'non è nella lista');
    });

    it('uno che si conosce già', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);
        $character->spells()->create(['name' => 'Fulmine', 'level' => 3]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Fulmine'],
        ))->toThrow(InvalidArgumentException::class, 'lo conosce già');
    });
// Il livello massimo apprendibile viene calcolato sugli slot che il personaggio avrà dopo il level-up.
    it('uno di livello troppo alto per gli slot che avrà', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Palla di Fuoco'],
        ))->toThrow(InvalidArgumentException::class, 'non ci arriva ancora');
    });

    it('ma i trucchetti si imparano sempre: non consumano slot', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Raggio di Gelo'],
        );

        expect($change->diff['spells'])->toBe(['Raggio di Gelo']);
    });
});

describe('l\'approvazione', function () {
    it('scrive gli incantesimi sulla scheda', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Palla di Fuoco'],
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $spell = $character->fresh()->spells()->where('name', 'Palla di Fuoco')->first();

        expect($spell)->not->toBeNull()
            ->and($spell->level)->toBe(3);
    });
// L'approvazione rivalida lo stato corrente perché lo stesso incantesimo può essere stato aggiunto nel frattempo.
    it('non sdoppia la lista se l\'incantesimo c\'è già', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 4]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, spells: ['Fulmine'],
        );

        $character->spells()->create(['name' => 'Fulmine', 'level' => 3]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->spells()->where('name', 'Fulmine')->count())->toBe(1);
    });
});

describe('le sottoclassi ora sono a catalogo', function () {
    it('accetta quelle vere', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, subclass: 'Evocazione',
        );

        expect($change->diff['subclass'])->toBe('Evocazione');
    });

    it('rifiuta il testo libero', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, subclass: 'Scuola del Panino',
        ))->toThrow(InvalidArgumentException::class, 'non è una sottoclasse');
    });

    it('e rifiuta una sottoclasse di un\'altra classe', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);
        $barbarian = App\Domain\Dnd\ClassRules::subclasses('Barbaro')[0];

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, subclass: $barbarian,
        ))->toThrow(InvalidArgumentException::class);
    });
});
