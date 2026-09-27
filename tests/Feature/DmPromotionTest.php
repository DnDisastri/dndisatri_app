<?php

declare(strict_types=1);

use App\Actions\Users\DemoteFromDm;
use App\Actions\Users\PromoteToDm;
use App\Enums\PendingChangeStatus;
use App\Models\Campaign;
use App\Models\DmRequest;
use App\Models\User;
use App\Notifications\DmRoleGranted;
use App\Notifications\DmRoleRevoked;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

// La nomina che parte dall'amministratore, senza che il giocatore l'abbia
// chiesta. L'altra strada resta ReviewDmRequest.
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->giocatore = User::factory()->player()->create(['name' => 'Grimm']);
});

describe('la nomina', function () {
    it('dà il ruolo e lo dice all\'interessato', function () {
        Notification::fake();

        app(PromoteToDm::class)->handle($this->giocatore, $this->admin);

        expect($this->giocatore->fresh()->isDm())->toBeTrue();

        Notification::assertSentTo($this->giocatore, DmRoleGranted::class);
    });

    it('la può fare solo un amministratore', function () {
        $dm = User::factory()->dm()->create();

        expect(fn () => app(PromoteToDm::class)->handle($this->giocatore, $dm))
            ->toThrow(RuntimeException::class);

        expect($this->giocatore->fresh()->isDm())->toBeFalse();
    });

    it('non tocca chi è già dungeon master', function () {
        $dm = User::factory()->dm()->create();

        expect(fn () => app(PromoteToDm::class)->handle($dm, $this->admin))
            ->toThrow(RuntimeException::class);
    });

    it('non si fa su un amministratore', function () {
        $altro = User::factory()->admin()->create();

        expect(fn () => app(PromoteToDm::class)->handle($altro, $this->admin))
            ->toThrow(RuntimeException::class);
    });

    // Altrimenti il pannello continuerebbe a segnalare una domanda da
    // decidere su qualcuno che è già stato nominato.
    it('chiude la domanda che quel giocatore aveva in sospeso', function () {
        $richiesta = DmRequest::factory()->from($this->giocatore)->create();

        app(PromoteToDm::class)->handle($this->giocatore, $this->admin);

        expect($richiesta->fresh()->status)->toBe(PendingChangeStatus::Approved)
            ->and($richiesta->fresh()->reviewed_by)->toBe($this->admin->id);
    });

    it('finisce nel registro delle attività', function () {
        app(PromoteToDm::class)->handle($this->giocatore, $this->admin);

        $riga = Activity::where('log_name', 'ruoli')->latest('id')->first();

        expect($riga)->not->toBeNull()
            ->and($riga->causer_id)->toBe($this->admin->id)
            ->and($riga->subject_id)->toBe($this->giocatore->id);
    });
});

describe('la revoca', function () {
    it('riporta il dungeon master fra i giocatori e glielo dice', function () {
        Notification::fake();

        $dm = User::factory()->dm()->create();

        app(DemoteFromDm::class)->handle($dm, $this->admin);

        expect($dm->fresh()->isDm())->toBeFalse();

        Notification::assertSentTo($dm, DmRoleRevoked::class);
    });

    it('la può fare solo un amministratore', function () {
        $dm = User::factory()->dm()->create();
        $altroDm = User::factory()->dm()->create();

        expect(fn () => app(DemoteFromDm::class)->handle($dm, $altroDm))
            ->toThrow(RuntimeException::class);

        expect($dm->fresh()->isDm())->toBeTrue();
    });

    it('non si fa su chi non è dungeon master', function () {
        expect(fn () => app(DemoteFromDm::class)->handle($this->giocatore, $this->admin))
            ->toThrow(RuntimeException::class);
    });

    // Una campagna con un master che non può più condurla resterebbe in piedi
    // e inutilizzabile, e il danno lo vedrebbero i giocatori.
    it('si rifiuta finché conduce una campagna aperta', function () {
        $dm = User::factory()->dm()->create();
        Campaign::factory()->runBy($dm)->create(['title' => 'La Tomba di Acererak']);

        expect(fn () => app(DemoteFromDm::class)->handle($dm, $this->admin))
            ->toThrow(RuntimeException::class, 'La Tomba di Acererak');

        expect($dm->fresh()->isDm())->toBeTrue();
    });

    it('si fa una volta che la campagna è chiusa', function () {
        $dm = User::factory()->dm()->create();
        Campaign::factory()->runBy($dm)->ended()->create();

        app(DemoteFromDm::class)->handle($dm, $this->admin);

        expect($dm->fresh()->isDm())->toBeFalse();
    });

    it('finisce nel registro delle attività', function () {
        $dm = User::factory()->dm()->create();

        app(DemoteFromDm::class)->handle($dm, $this->admin);

        expect(Activity::where('log_name', 'ruoli')->where('subject_id', $dm->id)->exists())->toBeTrue();
    });
});
