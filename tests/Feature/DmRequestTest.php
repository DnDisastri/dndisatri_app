<?php

declare(strict_types=1);

use App\Actions\Users\ReviewDmRequest;
use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;
use App\Models\User;

describe('il ruolo DM lo assegna solo il server', function () {
    it('approvando la richiesta, e per mano di un admin', function () {
        // Il ruolo DM viene assegnato solo dal server attraverso una richiesta approvata da un amministratore.
        $admin = User::factory()->admin()->create();
        $player = User::factory()->player()->create();
        $request = DmRequest::factory()->from($player)->create();

        expect($player->isDm())->toBeFalse();

        app(ReviewDmRequest::class)->handle($request, $admin, PendingChangeStatus::Approved);

        expect($player->fresh()->isDm())->toBeTrue()
            ->and($request->fresh()->status)->toBe(PendingChangeStatus::Approved)
            ->and($request->fresh()->reviewed_by)->toBe($admin->id);
    });

    it('e nessun altro può farlo, nemmeno un DM', function () {
        $dm = User::factory()->dm()->create();
        $request = DmRequest::factory()->create();

        expect(fn () => app(ReviewDmRequest::class)->handle($request, $dm, PendingChangeStatus::Approved))
            ->toThrow(RuntimeException::class);

        expect($request->fresh()->isPending())->toBeTrue()
            ->and($request->fresh()->user->isDm())->toBeFalse();
    });

    it('rifiutare non promuove nessuno', function () {
        $admin = User::factory()->admin()->create();
        $request = DmRequest::factory()->create();

        app(ReviewDmRequest::class)->handle($request, $admin, PendingChangeStatus::Rejected, 'Riparliamone fra qualche mese.');

        expect($request->fresh()->user->isDm())->toBeFalse()
            ->and($request->fresh()->review_note)->toContain('qualche mese');
    });

    it('una richiesta già decisa non si decide di nuovo', function () {
        $admin = User::factory()->admin()->create();
        $request = DmRequest::factory()->create();

        app(ReviewDmRequest::class)->handle($request, $admin, PendingChangeStatus::Rejected);

        expect(fn () => app(ReviewDmRequest::class)->handle($request->fresh(), $admin, PendingChangeStatus::Approved))
            ->toThrow(RuntimeException::class);

        expect($request->fresh()->user->isDm())->toBeFalse();
    });
});

describe('chi può chiedere', function () {
    it('i giocatori sì, chi è già DM o admin no', function () {
        expect(User::factory()->player()->create()->can('create', DmRequest::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', DmRequest::class))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('create', DmRequest::class))->toBeFalse();
    });

    it('una richiesta aperta alla volta', function () {
        $player = User::factory()->player()->create();
        DmRequest::factory()->from($player)->create();

        expect($player->can('create', DmRequest::class))->toBeFalse();
    });

    it('ma dopo un rifiuto si può riprovare', function () {
        $admin = User::factory()->admin()->create();
        $player = User::factory()->player()->create();
        $request = DmRequest::factory()->from($player)->create();

        app(ReviewDmRequest::class)->handle($request, $admin, PendingChangeStatus::Rejected);

        expect($player->fresh()->can('create', DmRequest::class))->toBeTrue();
    });
});

describe('la bacheca delle richieste DM', function () {
    it('la vedono solo gli admin', function () {
        expect(User::factory()->admin()->create()->can('viewAny', DmRequest::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('viewAny', DmRequest::class))->toBeFalse()
            ->and(User::factory()->player()->create()->can('viewAny', DmRequest::class))->toBeFalse();
    });

    it('ma il richiedente segue la propria', function () {
        $player = User::factory()->player()->create();
        $mine = DmRequest::factory()->from($player)->create();
        $someoneElse = DmRequest::factory()->create();

        expect($player->can('view', $mine))->toBeTrue()
            ->and($player->can('view', $someoneElse))->toBeFalse()
            ->and($player->can('cancel', $mine))->toBeTrue()
            ->and($player->can('approve', $mine))->toBeFalse();
    });
});
