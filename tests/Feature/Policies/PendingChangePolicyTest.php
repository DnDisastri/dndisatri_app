<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;

describe('la bacheca è condivisa', function () {
    it('un DM qualsiasi può approvare o rifiutare', function () {
        $change = PendingChange::factory()->create();

        foreach (User::factory()->dm()->count(3)->create() as $dm) {
            expect($dm->can('approve', $change))->toBeTrue()
                ->and($dm->can('reject', $change))->toBeTrue();
        }
    });

    it('anche un admin può', function () {
        $change = PendingChange::factory()->create();
        $admin = User::factory()->admin()->create();

        expect($admin->can('approve', $change))->toBeTrue();
    });

    it('un giocatore no', function () {
        $change = PendingChange::factory()->create();
        $player = User::factory()->player()->create();

        expect($player->can('approve', $change))->toBeFalse()
            ->and($player->can('reject', $change))->toBeFalse();
    });
});
// Chi propone una modifica non può anche approvarla, anche se è DM o admin.
describe('conflitto di interessi', function () {
    it('un DM non approva le richieste del proprio personaggio', function () {
        $dm = User::factory()->dm()->create();
        $ownCharacter = Character::factory()->ownedBy($dm)->create();
        $change = PendingChange::factory()->forCharacter($ownCharacter)->create();

        expect($dm->can('approve', $change))->toBeFalse()
            ->and($dm->can('reject', $change))->toBeFalse();
    });

    it('ma la approva un altro DM, o un admin', function () {
        $dm = User::factory()->dm()->create();
        $change = PendingChange::factory()->forCharacter(Character::factory()->ownedBy($dm)->create())->create();

        expect(User::factory()->dm()->create()->can('approve', $change))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('approve', $change))->toBeTrue();
    });

    it('vale anche per gli admin, che il Gate::before non scavalca', function () {
        $admin = User::factory()->admin()->create();
        $change = PendingChange::factory()->forCharacter(Character::factory()->ownedBy($admin)->create())->create();

        expect($admin->can('approve', $change))->toBeFalse()
            ->and($admin->can('view', $change))->toBeTrue();
    });
});

describe('una richiesta già decisa non si decide di nuovo', function () {
    it('né approvata né rifiutata', function () {
        $reviewer = User::factory()->dm()->create();
        $other = User::factory()->dm()->create();

        $approved = PendingChange::factory()->approvedBy($reviewer)->create();
        $rejected = PendingChange::factory()->rejectedBy($reviewer)->create();

        expect($other->can('approve', $approved))->toBeFalse()
            ->and($other->can('reject', $approved))->toBeFalse()
            ->and($other->can('approve', $rejected))->toBeFalse();
    });

    it('ma resta visibile, con la traccia di chi ha deciso', function () {
        $reviewer = User::factory()->dm()->create();
        $change = PendingChange::factory()->approvedBy($reviewer)->create();

        expect($change->reviewedBy->is($reviewer))->toBeTrue()
            ->and($change->reviewed_at)->not->toBeNull()
            ->and(User::factory()->player()->create()->can('view', $change))->toBeFalse();
    });
});

describe('chi ha deciso', function () {
    it('lo vedono solo DM e admin', function () {
        $player = User::factory()->player()->create();
        $change = PendingChange::factory()
            ->forCharacter(Character::factory()->ownedBy($player)->create())
            ->approvedBy(User::factory()->dm()->create())
            ->create();
// Il proponente vede richiesta ed esito, ma non l'identità di chi ha deciso.
        expect($player->can('view', $change))->toBeTrue()
            ->and($player->can('viewReviewer', $change))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('viewReviewer', $change))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('viewReviewer', $change))->toBeTrue();
    });
});

describe('il proponente', function () {
    it('vede e può ritirare la propria richiesta', function () {
        $player = User::factory()->player()->create();
        $change = PendingChange::factory()->forCharacter(Character::factory()->ownedBy($player)->create())->create();

        expect($player->can('view', $change))->toBeTrue()
            ->and($player->can('cancel', $change))->toBeTrue();
    });

    it('non ritira più nulla una volta decisa', function () {
        $player = User::factory()->player()->create();
        $change = PendingChange::factory()
            ->forCharacter(Character::factory()->ownedBy($player)->create())
            ->approvedBy(User::factory()->dm()->create())
            ->create();

        expect($player->can('cancel', $change))->toBeFalse();
    });

    it('non vede le richieste degli altri giocatori', function () {
        $change = PendingChange::factory()->create();
        $stranger = User::factory()->player()->create();

        expect($stranger->can('view', $change))->toBeFalse();
    });
});
// La scheda può cambiare mentre la richiesta è pendente: l'approvazione deve rilevare i conflitti.
describe('la scheda cambiata nel frattempo', function () {
    it('marca la richiesta come obsoleta', function () {
        $character = Character::factory()->create();
        $change = PendingChange::factory()->forCharacter($character)->create();

        expect($change->isStale())->toBeFalse();

        $this->travel(1)->hours();
        $character->touch();

        expect($change->fresh()->load('character')->isStale())->toBeTrue();
    });

    it('non marca mai obsoleti i bottini, che si sommano', function () {
        $character = Character::factory()->create();
        $change = PendingChange::factory()->forCharacter($character)->loot()->create();

        $this->travel(1)->hours();
        $character->touch();

        expect($change->fresh()->load('character')->isStale())->toBeFalse();
    });
});

describe('la bacheca filtrata per utente', function () {
    it('mostra tutto a DM e admin, solo le proprie ai giocatori', function () {
        $player = User::factory()->player()->create();
        $mine = PendingChange::factory()->forCharacter(Character::factory()->ownedBy($player)->create())->create();
        PendingChange::factory()->count(2)->create();

        expect(PendingChange::visibleTo($player)->pluck('id')->all())->toBe([$mine->id])
            ->and(PendingChange::visibleTo(User::factory()->dm()->create())->count())->toBe(3)
            ->and(PendingChange::visibleTo(User::factory()->admin()->create())->count())->toBe(3);
    });
});
