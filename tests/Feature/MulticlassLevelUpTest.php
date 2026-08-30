<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\RequestLevelUp;
use App\Models\Character;
use App\Models\User;

// Il multiclasse nasce solo all'approvazione della richiesta; fino ad allora la scheda resta invariata.
function guerriero(array $overrides = []): Character
{
    $character = Character::factory()->create([
        'class' => 'Guerriero', 'hit_die' => 10, 'level' => 3,
        'str' => 16, 'dex' => 14, 'con' => 14, 'int' => 14, 'wis' => 10, 'cha' => 10,
        'hp_max' => 30, 'hp_current' => 30,
        ...$overrides,
    ]);

    $character->classes()->create([
        'class' => $character->class, 'level' => $character->level, 'is_primary' => true,
    ]);

    return $character->fresh();
}

describe('la richiesta', function () {
    it('dice quale classe e usa il suo dado vita', function () {
        $character = guerriero();

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');
        expect($change->summary)->toContain('Nuova classe: Mago 1.')
            ->and($change->summary)->toContain('+6 PF (d6)')
            ->and($change->diff['hp_max'])->toBe(36)
            ->and($change->diff['class_up']['is_new'])->toBeTrue();
    });

    it('salendo in una classe che si ha già, non è nuova', function () {
        $character = guerriero();

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Guerriero');

        expect($change->summary)->toContain('Guerriero 3 → 4.')
            ->and($change->diff['class_up']['is_new'])->toBeFalse()
            ->and($change->summary)->toContain('+8 PF (d10)');
    });

    it('senza dire la classe si sale nella principale', function () {
        $character = guerriero();

        $change = app(RequestLevelUp::class)->handle($character, $character->user);

        expect($change->diff['class_up']['class'])->toBe('Guerriero');
    });

    it('una classe inventata viene rifiutata', function () {
        $character = guerriero();

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Panettiere',
        ))->toThrow(InvalidArgumentException::class, 'Classe sconosciuta');
    });
});
// I requisiti di multiclassing generano un avviso ma non bloccano la richiesta: la decisione finale resta al DM.
describe('i requisiti avvisano ma non impediscono', function () {
    it('la richiesta parte lo stesso, con scritto cosa manca', function () {
        $character = guerriero(['int' => 9]);

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');

        expect($change->diff['class_up']['unmet'])->toContain('Mago richiede 13 in Intelligenza')
            ->and($change->summary)->toContain('Requisiti non soddisfatti');
    });

    it('e un DM può approvarla comunque', function () {
        $character = guerriero(['int' => 9]);

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->levelIn('Mago'))->toBe(1);
    });

    it('con i punteggi giusti non manca niente', function () {
        $change = app(RequestLevelUp::class)->handle(guerriero(), guerriero()->user, class: 'Mago');

        expect($change->diff['class_up']['unmet'])->toBe([]);
    });
});
// Il limite riguarda il numero di classi distinte, non i livelli presi in classi già possedute.
describe('il limite di tre classi', function () {
    it('la quarta viene rifiutata', function () {
        $character = guerriero();
        $character->classes()->create(['class' => 'Mago', 'level' => 1]);
        $character->classes()->create(['class' => 'Ladro', 'level' => 1]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character->fresh(), $character->user, class: 'Chierico',
        ))->toThrow(InvalidArgumentException::class, 'più di 3 classi');
    });

    it('ma si può sempre salire in una che si ha già', function () {
        $character = guerriero();
        $character->classes()->create(['class' => 'Mago', 'level' => 1]);
        $character->classes()->create(['class' => 'Ladro', 'level' => 1]);

        $change = app(RequestLevelUp::class)->handle(
            $character->fresh(), $character->user, class: 'Mago',
        );

        expect($change->diff['class_up']['level'])->toBe(2);
    });
});

describe('l\'approvazione', function () {
    it('crea la riga della classe e aggiorna il livello totale', function () {
        $character = guerriero();

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $fresh = $character->fresh();

        expect($fresh->classLevels())->toBe(['Guerriero' => 3, 'Mago' => 1])
            ->and($fresh->level)->toBe(4)
            ->and($fresh->class)->toBe('Guerriero')
            ->and($fresh->isMulticlass())->toBeTrue();
    });

    it('la classe principale resta la prima presa', function () {
        $character = guerriero();

        $change = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->primaryClass()->class)->toBe('Guerriero');
    });

    it('e gli slot diventano quelli del multiclasse', function () {
        $character = guerriero();

        foreach ([1, 2] as $ignored) {
            $change = app(RequestLevelUp::class)->handle($character->fresh(), $character->user, class: 'Mago');
            app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());
        }

        expect($character->fresh()->classLevels())->toBe(['Guerriero' => 3, 'Mago' => 2])
            ->and($character->fresh()->spellSlots()->slots)->toBe([1 => 3]);
    });
});

describe('le competenze entrando in una classe nuova', function () {
    it('il Ladro ne dà una, e si aggiunge a quelle che ci sono', function () {
        $character = guerriero();
        $before = array_keys($character->skills ?? []);

        $change = app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Ladro', skills: ['stealth'],
        );
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $after = $character->fresh()->skills;

        expect($after)->toHaveKey('stealth')
            ->and(array_keys($after))->toContain(...$before);
    });

    it('il Mago non ne dà nessuna', function () {
        $character = guerriero();

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Mago', skills: ['arcana'],
        ))->toThrow(InvalidArgumentException::class, 'non si scelgono abilità');
    });

    it('e non si sceglie fuori dall\'elenco della classe', function () {
        $character = guerriero();

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Ladro', skills: ['arcana'],
        ))->toThrow(InvalidArgumentException::class);
    });

    it('salendo in una classe che si ha già non se ne scelgono', function () {
        $character = guerriero();

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Guerriero', skills: ['stealth'],
        ))->toThrow(InvalidArgumentException::class, 'classe nuova');
    });
});
// Il livello di accesso alla sottoclasse si calcola sulla singola classe, non sul livello totale del personaggio.
describe('la sottoclasse', function () {
    it('si sceglie al livello di quella classe, non del personaggio', function () {
        $character = guerriero();

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $character->user, class: 'Mago', subclass: 'Evocazione',
        ))->toThrow(InvalidArgumentException::class, 'livello 2 della classe');
    });

    it('al livello giusto entra, e resta sulla riga della classe', function () {
        $character = guerriero();

        $primo = app(RequestLevelUp::class)->handle($character, $character->user, class: 'Mago');
        app(ApprovePendingChange::class)->handle($primo, User::factory()->dm()->create());

        $secondo = app(RequestLevelUp::class)->handle(
            $character->fresh(), $character->user, class: 'Mago', subclass: 'Evocazione',
        );
        app(ApprovePendingChange::class)->handle($secondo, User::factory()->dm()->create());

        $fresh = $character->fresh();

        expect($fresh->classes->firstWhere('class', 'Mago')->subclass)->toBe('Evocazione')

            ->and($fresh->subclass)->toBeNull();
    });
});

describe('il modulo', function () {
    it('elenca le classi che si hanno e quelle che si possono prendere', function () {
        $character = guerriero();

        $this->actingAs($character->user)
            ->get(route('proposals.level-up', $character))
            ->assertOk()
            ->assertSee('Guerriero 3 → 4')
            ->assertSee('Prendi una classe nuova');
    });

    it('scegliendo una classe nuova, il modulo si adegua', function () {
        $character = guerriero();

        $this->actingAs($character->user)
            ->get(route('proposals.level-up', [$character, 'classe' => 'Ladro']))
            ->assertOk()
            ->assertSee('Competenze da Ladro');
    });

    it('mostra i requisiti mancanti senza impedire di chiedere', function () {
        $character = guerriero(['int' => 9]);

        $this->actingAs($character->user)
            ->get(route('proposals.level-up', [$character, 'classe' => 'Mago']))
            ->assertOk()
            ->assertSee('Non hai i requisiti per Mago')
            ->assertSee('sarà un DM a decidere');
    });

    it('con tre classi non ne propone altre', function () {
        $character = guerriero();
        $character->classes()->create(['class' => 'Mago', 'level' => 1]);
        $character->classes()->create(['class' => 'Ladro', 'level' => 1]);

        $this->actingAs($character->user)
            ->get(route('proposals.level-up', $character->fresh()))
            ->assertOk()
            ->assertDontSee('Prendi una classe nuova')
            ->assertSee('non se ne prendono altre');
    });

    it('e l\'invio arriva alla richiesta con la classe scelta', function () {
        $character = guerriero();

        $this->actingAs($character->user)
            ->post(route('proposals.level-up', $character), [
                'class' => 'Ladro',
                'skills' => ['stealth'],
            ])
            ->assertRedirect();

        $change = $character->pendingChanges()->latest('id')->first();

        expect($change->diff['class_up']['class'])->toBe('Ladro')
            ->and($change->diff['class_up']['skills'])->toBe(['stealth']);
    });
});
