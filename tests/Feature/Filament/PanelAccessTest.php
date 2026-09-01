<?php

declare(strict_types=1);

use App\Filament\Resources\DmRequests\DmRequestResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\MarketItems\MarketItemResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
// Filament delega l'accesso alle policy: questi test impediscono che una Resource resti aperta per una policy mancante.
$adminOnly = [
    'richieste DM' => DmRequestResource::class,
    'news' => PostResource::class,
    'eventi' => EventResource::class,
    'catalogo' => MarketItemResource::class,
];

describe('le sezioni riservate agli admin', function () use ($adminOnly) {
    it('si aprono per un admin', function (string $resource) {
        $this->actingAs(User::factory()->admin()->create())
            ->get($resource::getUrl('index'))
            ->assertOk();
    })->with($adminOnly);

    it('sono chiuse ai DM', function (string $resource) {
        $this->actingAs(User::factory()->dm()->create())
            ->get($resource::getUrl('index'))
            ->assertForbidden();
    })->with($adminOnly);

    it('non compaiono nemmeno nel menu di un DM', function (string $resource) {
        $this->actingAs(User::factory()->dm()->create());

        expect($resource::canViewAny())->toBeFalse();
    })->with($adminOnly);
});

describe('la sezione Utenti', function () {
    it('si apre ai DM in sola lettura', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm)
            ->get(UserResource::getUrl('index'))
            ->assertOk();

        expect($dm->can('viewAny', User::class))->toBeTrue()
            ->and($dm->can('view', User::factory()->player()->create()))->toBeTrue();
    });

    it('non lascia ai DM creare, modificare o approvare account', function () {
        $dm = User::factory()->dm()->create();

        expect($dm->can('create', User::class))->toBeFalse()
            ->and($dm->can('update', User::factory()->player()->create()))->toBeFalse()
            ->and($dm->can('delete', User::factory()->player()->create()))->toBeFalse();
    });
});

describe('il pannello', function () {
    it('è chiuso ai giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get('/admin')
            ->assertForbidden();
    });

    it('non lascia cancellare account nemmeno agli admin', function () {
        // La cancellazione degli utenti resta disabilitata per preservare personaggi e storico.
        $admin = User::factory()->admin()->create();

        expect($admin->can('delete', User::factory()->player()->create()))->toBeFalse();
    });
});
