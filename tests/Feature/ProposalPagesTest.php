<?php

declare(strict_types=1);

use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;

describe('chi può proporre', function () {
    it('solo il proprietario, e solo se il personaggio è vivo', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create();
        $stranger = User::factory()->player()->create();

        $this->actingAs($owner)->get(route('proposals.edit', $character))->assertOk();
        $this->actingAs($stranger)->get(route('proposals.edit', $character))->assertForbidden();

        $fallen = Character::factory()->ownedBy($owner)->fallen()->create();
        $this->actingAs($owner)->get(route('proposals.level-up', $fallen))->assertForbidden();
    });

    it('i pulsanti compaiono solo al proprietario', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->get(route('characters.show', $character))
            ->assertSee('Sali di livello');

        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.show', $character))
            ->assertDontSee('Sali di livello');
    });
});
// La proposta registra il nuovo stato richiesto senza modificare la scheda finché un DM non la approva.
describe('proporre una modifica', function () {
    it('crea la richiesta senza toccare la scheda', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create(['notes' => 'vecchie']);

        $this->actingAs($owner)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'background' => $character->background,
                'notes' => 'nuove',
            ])
            ->assertRedirect(route('characters.show', $character))
            ->assertSessionHas('status');

        expect($character->fresh()->notes)->toBe('vecchie')
            ->and(PendingChange::first()->diff)->toBe(['notes' => 'nuove']);
    });

    it('avvisa se non è cambiato niente', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create(['notes' => 'uguali']);

        $this->actingAs($owner)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'background' => $character->background,
                'species_traits' => $character->species_traits,
                'class_features' => $character->class_features,
                'subclass_features' => $character->subclass_features,
                'notes' => 'uguali',
            ])
            ->assertSessionHasErrors('proposta');

        expect(PendingChange::count())->toBe(0);
    });
});

describe('chiedere il passaggio di livello', function () {
    it('mostra le scelte ASI solo ai livelli giusti', function () {
        $owner = User::factory()->player()->create();

        $toFourth = Character::factory()->ownedBy($owner)->create(['level' => 3]);
        $this->actingAs($owner)->get(route('proposals.level-up', $toFourth))
            ->assertOk()->assertSee('Aumento di caratteristica o talento');

        $toThird = Character::factory()->ownedBy($owner)->create(['level' => 2]);
        $this->actingAs($owner)->get(route('proposals.level-up', $toThird))
            ->assertOk()->assertDontSee('Aumento di caratteristica o talento');
    });

    it('manda la richiesta col calcolo dei PF già fatto', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create([
            'level' => 7, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 15, 'hp_max' => 52, 'hp_current' => 52,
        ]);

        $this->actingAs($owner)
            ->post(route('proposals.level-up', $character), [
                'asi_mode' => 'plus2',
                'asi_first' => 'con',
            ])
            ->assertRedirect(route('characters.show', $character));
// Il calcolo del level-up viene salvato nella richiesta, mentre il personaggio resta invariato in attesa della decisione.
        $change = PendingChange::first();

        expect($change->type)->toBe(PendingChangeType::LevelUp)
            ->and($change->diff['hp_max'])->toBe(68)
            ->and($change->summary)->toContain('retroattivi')
            ->and($character->fresh()->level)->toBe(7);
    });

    it('rifiuta un +1/+1 sulla stessa caratteristica con un messaggio chiaro', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create(['level' => 3]);

        $this->actingAs($owner)
            ->post(route('proposals.level-up', $character), [
                'asi_mode' => 'plus1', 'asi_first' => 'str', 'asi_second' => 'str',
            ])
            ->assertSessionHasErrors('proposta');
    });
});

describe('registrare un bottino', function () {
    it('somma oro e oggetti, saltando le righe vuote', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('proposals.loot', $character), [
                'gp' => 150,
                'items' => [
                    ['name' => 'Spada Lunga', 'qty' => 1],
                    ['name' => '', 'qty' => null],
                ],
                'note' => 'Drago rosso',
            ])
            ->assertRedirect(route('characters.show', $character));

        $change = PendingChange::first();

        expect($change->grant_gp)->toBe(150)
            ->and($change->grant_items)->toHaveCount(1)
            ->and($change->summary)->toContain('Drago rosso');
    });

    it('non accetta un bottino vuoto', function () {
        $owner = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('proposals.loot', $character), ['gp' => 0])
            ->assertSessionHasErrors('proposta');
    });
});

describe('le mie richieste', function () {
    it('mostrano solo le proprie, con l\'esito ma senza chi ha deciso', function () {
        $owner = User::factory()->player()->create();
        $dm = User::factory()->dm()->create(['name' => 'Aurelio il Narratore']);

        $mine = PendingChange::factory()
            ->forCharacter(Character::factory()->ownedBy($owner)->create())
            ->approvedBy($dm)
            ->create(['summary' => 'La mia richiesta']);

        PendingChange::factory()->create(['summary' => 'Richiesta di un altro']);
// Il giocatore vede l'esito della propria richiesta ma non l'identità di chi l'ha revisionata.
        $this->actingAs($owner)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('La mia richiesta')
            ->assertSee('Approvata')
            ->assertDontSee('Richiesta di un altro')
            ->assertDontSee('Aurelio il Narratore');
    });

    it('mostrano la nota di chi ha rifiutato', function () {
        $owner = User::factory()->player()->create();
        $change = PendingChange::factory()
            ->forCharacter(Character::factory()->ownedBy($owner)->create())
            ->create([
                'status' => PendingChangeStatus::Rejected,
                'review_note' => 'Riparliamone dopo la prossima sessione.',
            ]);

        $this->actingAs($owner)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('Riparliamone dopo la prossima sessione.');
    });
});
// L'archiviazione nasconde le richieste già decise senza cancellarle; quelle ancora pendenti restano attive.
describe('archiviare le richieste', function () {
    beforeEach(function () {
        $this->owner = User::factory()->player()->create();
        $this->personaggio = Character::factory()->ownedBy($this->owner)->create();
    });

    it('una decisa si archivia e sparisce dalla lista attiva', function () {
        $decisa = PendingChange::factory()
            ->forCharacter($this->personaggio)
            ->approvedBy(User::factory()->dm()->create())
            ->create(['summary' => 'Modifica decisa']);

        $this->actingAs($this->owner)
            ->post(route('proposals.archive', $decisa))
            ->assertRedirect();

        expect($decisa->fresh()->archived_at)->not->toBeNull();

        $this->actingAs($this->owner)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertDontSee('Modifica decisa');
    });

    it('ma resta, e si rivede in archivio', function () {
        PendingChange::factory()
            ->forCharacter($this->personaggio)
            ->approvedBy(User::factory()->dm()->create())
            ->create(['summary' => 'Modifica decisa', 'archived_at' => now()]);

        $this->actingAs($this->owner)
            ->get(route('proposals.index', ['archiviate' => 1]))
            ->assertOk()
            ->assertSee('Modifica decisa');
    });

    it('una in attesa non si archivia: è viva', function () {
        $attesa = PendingChange::factory()
            ->forCharacter($this->personaggio)
            ->create(['summary' => 'Ancora da decidere']);

        $this->actingAs($this->owner)
            ->post(route('proposals.archive', $attesa))
            ->assertForbidden();

        expect($attesa->fresh()->archived_at)->toBeNull();
    });

    it('«svuota» mette via le decise e lascia le in attesa', function () {
        PendingChange::factory()->forCharacter($this->personaggio)
            ->approvedBy(User::factory()->dm()->create())->create();
        $attesa = PendingChange::factory()->forCharacter($this->personaggio)->create();

        $this->actingAs($this->owner)
            ->post(route('proposals.clear'))
            ->assertRedirect();

        expect(PendingChange::visibleTo($this->owner)->notArchived()->decided()->count())->toBe(0)
            ->and($attesa->fresh()->archived_at)->toBeNull();
    });

    it('dall\'archivio si ripesca', function () {
        $archiviata = PendingChange::factory()
            ->forCharacter($this->personaggio)
            ->approvedBy(User::factory()->dm()->create())
            ->create(['archived_at' => now()]);

        $this->actingAs($this->owner)
            ->post(route('proposals.restore', $archiviata))
            ->assertRedirect();

        expect($archiviata->fresh()->archived_at)->toBeNull();
    });

    it('quella di un altro non la si tocca', function () {
        $altrui = PendingChange::factory()
            ->approvedBy(User::factory()->dm()->create())
            ->create();

        $this->actingAs($this->owner)
            ->post(route('proposals.archive', $altrui))
            ->assertNotFound();
    });
});
