<?php

declare(strict_types=1);

use App\Actions\Users\ReviewDmRequest;
use App\Enums\PendingChangeStatus;
use App\Filament\Resources\DmRequests\DmRequestResource;
use App\Models\DmRequest;
use App\Models\User;

describe('la bacheca delle richieste DM', function () {
    it('si apre per un admin', function () {
        DmRequest::factory()->count(3)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(DmRequestResource::getUrl('index'))
            ->assertOk();
    });

    it('è chiusa ai DM, che nel menu non la vedono nemmeno', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm)
            ->get(DmRequestResource::getUrl('index'))
            ->assertForbidden();

        expect(DmRequestResource::canViewAny())->toBeFalse();
    });

    it('mostra il contatore delle richieste aperte', function () {
        expect(DmRequestResource::getNavigationBadge())->toBeNull();

        DmRequest::factory()->count(2)->create();

        expect(DmRequestResource::getNavigationBadge())->toBe('2');
    });

    it('non conta quelle già decise', function () {
        $admin = User::factory()->admin()->create();
        $request = DmRequest::factory()->create();

        app(ReviewDmRequest::class)
            ->handle($request, $admin, PendingChangeStatus::Rejected);

        expect(DmRequestResource::getNavigationBadge())->toBeNull();
    });

    it('apre il dettaglio di una richiesta', function () {
        $request = DmRequest::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(DmRequestResource::getUrl('view', ['record' => $request]))
            ->assertOk()
            ->assertSee($request->user->name);
    });

    it('non espone nessuna pagina di creazione o modifica', function () {
        expect(array_keys(DmRequestResource::getPages()))->toBe(['index', 'view']);
    });
});
