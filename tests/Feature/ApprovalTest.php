<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\RejectPendingChange;
use App\Domain\Dnd\Ability;
use App\Enums\LedgerAction;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\PendingChange;
use App\Models\User;

describe('modifica di scheda approvata', function () {
    it('applica i campi proposti', function () {
        $character = Character::factory()->create(['notes' => 'vecchie note']);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['notes' => 'note nuove', 'background' => 'Criminale'],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->notes)->toBe('note nuove')
            ->and($character->fresh()->background)->toBe('Criminale')
            ->and($change->fresh()->status)->toBe(PendingChangeStatus::Approved);
    });

    it('registra chi ha approvato', function () {
        $dm = User::factory()->dm()->create();
        $change = PendingChange::factory()->create();

        app(ApprovePendingChange::class)->handle($change, $dm, 'Va bene così.');

        expect($change->fresh()->reviewed_by)->toBe($dm->id)
            ->and($change->fresh()->reviewed_at)->not->toBeNull()
            ->and($change->fresh()->review_note)->toBe('Va bene così.');
    });
});
// Il diff nasce da input del giocatore: l'approvazione applica solo i campi consentiti.
describe('quello che una richiesta NON può cambiare', function () {
    it('non tocca l\'oro: quello si muove solo dal mercato e dal DM', function () {

        $character = Character::factory()->create(['gp' => 100]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['notes' => 'innocue', 'gp' => 999999],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->gp)->toBe(100)
            ->and($character->fresh()->notes)->toBe('innocue');
    });

    it('non cambia il livello: quello passa dal passaggio di livello', function () {
        $character = Character::factory()->create(['level' => 3]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['level' => 20],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->level)->toBe(3);
    });

    it('non regala né toglie la vita, e non cambia proprietario', function () {
        $owner = User::factory()->player()->create();
        $altro = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($owner)->create();

        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['user_id' => $altro->id, 'died_at' => null, 'notes' => 'ok'],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->user_id)->toBe($owner->id);
    });
});

describe('passaggio di livello approvato', function () {
    it('applica livello, punteggi e punti ferita già calcolati', function () {
        $character = Character::factory()->create([
            'level' => 7, 'con' => 15, 'hp_max' => 52, 'hp_current' => 52, 'hit_die' => 10,
        ]);
// Il calcolo del passaggio di livello avviene alla proposta; qui si applicano soltanto i valori già calcolati.
        $change = PendingChange::factory()->forCharacter($character)->levelUp(8)->create([
            'diff' => ['level' => 8, 'con' => 16, 'hp_max' => 68, 'hp_current' => 68],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $character->refresh();

        expect($character->level)->toBe(8)
            ->and($character->con)->toBe(16)
            ->and($character->hp_max)->toBe(68)
            ->and($character->proficiencyBonus())->toBe(3);
    });
});

describe('bottino approvato', function () {
    it('somma l\'oro invece di sostituirlo', function () {
        $character = Character::factory()->create(['gp' => 100]);
        $change = PendingChange::factory()->forCharacter($character)->loot(250)->create();
// Il bottino viene sommato allo stato corrente, che può essere cambiato mentre la richiesta era pendente.
        $character->decrement('gp', 40);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->gp)->toBe(310);
    });

    it('aggiunge gli oggetti all\'inventario', function () {
        $character = Character::factory()->create();
        $change = PendingChange::factory()->forCharacter($character)->loot(50)->create();

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->ownsItem('Pozione di Cura'))->toBeTrue();
    });

    it('scrive il movimento nel Registro', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['gp' => 0]);
        $change = PendingChange::factory()->forCharacter($character)->loot(250)->create();

        app(ApprovePendingChange::class)->handle($change, $dm);

        $entry = LedgerEntry::forCharacter($character)->latestFirst()->first();

        expect($entry->action)->toBe(LedgerAction::Approve)
            ->and($entry->gp_delta)->toBe(250)
            ->and($entry->gp_after)->toBe(250)
            ->and($entry->actor_id)->toBe($dm->id);
    });
});

describe('oggetto magico approvato', function () {
    it('diventa un effetto sui punteggi efficaci', function () {
        $character = Character::factory()->create(['str' => 12]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'type' => PendingChangeType::ItemEffect,
            'diff' => [
                'name' => 'Cintura di Forza del Gigante',
                'ability' => 'str',
                'mode' => 'set',
                'value' => 21,
            ],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $character->refresh()->load('itemEffects', 'items');

        expect($character->itemEffects)->toHaveCount(1)
            ->and($character->baseScores()->score(Ability::Str))->toBe(12)
            ->and($character->effectiveScores()->score(Ability::Str))->toBe(21);
    });
});

describe('richieste già decise', function () {
    it('non si approvano due volte', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['gp' => 0]);
        $change = PendingChange::factory()->forCharacter($character)->loot(100)->create();

        app(ApprovePendingChange::class)->handle($change, $dm);

        expect(fn () => app(ApprovePendingChange::class)->handle($change->fresh(), $dm))
            ->toThrow(RuntimeException::class);

        expect($character->fresh()->gp)->toBe(100);
    });

    it('non si rifiutano dopo essere state approvate', function () {
        $dm = User::factory()->dm()->create();
        $change = PendingChange::factory()->create();

        app(ApprovePendingChange::class)->handle($change, $dm);

        expect(fn () => app(RejectPendingChange::class)->handle($change->fresh(), $dm))
            ->toThrow(RuntimeException::class);
    });
});

describe('richiesta rifiutata', function () {
    it('non tocca il personaggio', function () {
        $character = Character::factory()->create(['notes' => 'intatte', 'gp' => 100]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['notes' => 'mai applicate'],
        ]);

        app(RejectPendingChange::class)->handle($change, User::factory()->dm()->create(), 'No.');

        expect($character->fresh()->notes)->toBe('intatte')
            ->and($change->fresh()->status)->toBe(PendingChangeStatus::Rejected)
            ->and($change->fresh()->review_note)->toBe('No.')
            ->and(LedgerEntry::forCharacter($character)->count())->toBe(0);
    });
});
