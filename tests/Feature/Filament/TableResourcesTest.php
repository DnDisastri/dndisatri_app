<?php

declare(strict_types=1);

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\Maps\MapResource;
use App\Filament\Resources\Quests\QuestResource;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Map;
use App\Models\Quest;
use App\Models\User;

// I DM accedono alle risorse dei tavoli, ma le operazioni restano limitate dalle relative policy.
$tableSections = [
    'campagne' => CampaignResource::class,
    'quest' => QuestResource::class,
    'sessioni' => GameSessionResource::class,
    'mappe' => MapResource::class,
];

describe('le sezioni dei tavoli', function () use ($tableSections) {
    it('si aprono per i DM', function (string $resource) {
        $this->actingAs(User::factory()->dm()->create())
            ->get($resource::getUrl('index'))
            ->assertOk();
    })->with($tableSections);

    it('si aprono per gli admin', function (string $resource) {
        $this->actingAs(User::factory()->admin()->create())
            ->get($resource::getUrl('index'))
            ->assertOk();
    })->with($tableSections);

    it('restano chiuse ai giocatori, che nel pannello non entrano', function (string $resource) {
        $this->actingAs(User::factory()->player()->create())
            ->get($resource::getUrl('index'))
            ->assertForbidden();
    })->with($tableSections);
});

describe('le campagne', function () {
    it('sono modificabili solo dal DM che le conduce', function () {
        $owner = User::factory()->dm()->create();
        $other = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();

        expect($owner->can('update', $campaign))->toBeTrue()
            ->and($other->can('update', $campaign))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('update', $campaign))->toBeTrue();
    });

    it('si aprono in modifica per il proprietario', function () {
        $owner = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();

        $this->actingAs($owner)
            ->get(CampaignResource::getUrl('edit', ['record' => $campaign]))
            ->assertOk();
    });

    it('ma non per un altro DM', function () {
        $campaign = Campaign::factory()->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(CampaignResource::getUrl('edit', ['record' => $campaign]))
            ->assertForbidden();
    });
});

describe('quest e sessioni', function () {
    it('le può creare chiunque conduca, e il tavolo si sceglie nel modulo', function () {
// Filament verifica il permesso di creazione prima che esista una campagna specifica.
        $dm = User::factory()->dm()->create();

        expect($dm->can('create', Quest::class))->toBeTrue()
            ->and($dm->can('create', GameSession::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', Quest::class))->toBeFalse();
    });

    it('ma su un tavolo altrui no', function () {
        $dm = User::factory()->dm()->create();
        $altrui = Campaign::factory()->create();

        expect($dm->can('create', [Quest::class, $altrui]))->toBeFalse()
            ->and($dm->can('create', [GameSession::class, $altrui]))->toBeFalse();
    });

    it('si aprono in modifica per il DM del tavolo', function () {
        $owner = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();
        $quest = Quest::factory()->inCampaign($campaign)->create();

        $this->actingAs($owner)
            ->get(QuestResource::getUrl('edit', ['record' => $quest]))
            ->assertOk();
    });
});

describe('le mappe', function () {
    it('una generale la modifica qualsiasi DM', function () {
        $map = Map::factory()->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(MapResource::getUrl('edit', ['record' => $map]))
            ->assertOk();
    });

    it('una di un tavolo solo il suo DM', function () {
        $map = Map::factory()->forCampaign(Campaign::factory()->create())->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(MapResource::getUrl('edit', ['record' => $map]))
            ->assertForbidden();
    });
});
