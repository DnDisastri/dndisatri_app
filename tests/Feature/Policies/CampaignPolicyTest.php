<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;

describe('creazione delle campagne', function () {
    it('è riservata a DM e admin', function () {
        expect(User::factory()->dm()->create()->can('create', Campaign::class))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('create', Campaign::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', Campaign::class))->toBeFalse();
    });
});

describe('gestione del proprio tavolo', function () {
    it('il DM modifica e chiude solo le proprie campagne', function () {
        $dm = User::factory()->dm()->create();
        $mine = Campaign::factory()->runBy($dm)->create();
        $theirs = Campaign::factory()->create();

        expect($dm->can('update', $mine))->toBeTrue()
            ->and($dm->can('end', $mine))->toBeTrue()
            ->and($dm->can('update', $theirs))->toBeFalse()
            ->and($dm->can('end', $theirs))->toBeFalse();
    });

    it('una campagna conclusa non si chiude una seconda volta', function () {
        $dm = User::factory()->dm()->create();
        $ended = Campaign::factory()->runBy($dm)->ended()->create();

        expect($ended->isActive())->toBeFalse()
            ->and($dm->can('end', $ended))->toBeFalse()
// Una campagna conclusa può ancora correggere dati d'archivio, ma non modificare lo stato di gioco.
            ->and($dm->can('update', $ended))->toBeTrue();
    });
});

describe('i giocatori', function () {
    it('vedono le campagne ma non le gestiscono', function () {
        $player = User::factory()->player()->create();
        $campaign = Campaign::factory()->create();

        expect($player->can('view', $campaign))->toBeTrue()
            ->and($player->can('viewAny', Campaign::class))->toBeTrue()
            ->and($player->can('update', $campaign))->toBeFalse()
            ->and($player->can('end', $campaign))->toBeFalse();
    });
});

describe('gli admin', function () {
    it('gestiscono anche le campagne altrui', function () {
        $admin = User::factory()->admin()->create();
        $campaign = Campaign::factory()->create();

        expect($admin->can('update', $campaign))->toBeTrue()
            ->and($admin->can('end', $campaign))->toBeTrue()
            ->and($admin->can('delete', $campaign))->toBeTrue();
    });
});
