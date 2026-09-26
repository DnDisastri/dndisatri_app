<?php

declare(strict_types=1);

use App\Actions\Users\IssueWarning;
use App\Actions\Users\LiftWarning;
use App\Models\User;
use App\Models\Warning;
use App\Notifications\WarningIssued;
use App\Notifications\WarningLifted;
use Illuminate\Support\Facades\Notification;

describe('dare un richiamo', function () {
    it('mette il giocatore sotto controllo', function () {
        $player = User::factory()->player()->create();

        app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Scambio in malafede.');

        expect($player->fresh()->isUnderWarning())->toBeTrue();
    });

    it('avvisa il richiamato, spiegando cosa cambia', function () {
        Notification::fake();

        $player = User::factory()->player()->create();
        app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Scambio in malafede.');

        Notification::assertSentTo($player, WarningIssued::class);
    });

    it('la notifica riporta il motivo', function () {
        $player = User::factory()->player()->create();
        app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Scambio in malafede.');

        expect($player->notifications()->first()->data['body'])->toContain('Scambio in malafede.');
    });

    it('serve un motivo', function () {
        $player = User::factory()->player()->create();

        expect(fn () => app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), '  '))
            ->toThrow(InvalidArgumentException::class);
    });

    it('uno alla volta', function () {
        $player = User::factory()->player()->create();
        $dm = User::factory()->dm()->create();

        app(IssueWarning::class)->handle($player, $dm, 'Primo.');

        expect(fn () => app(IssueWarning::class)->handle($player->fresh(), $dm, 'Secondo.'))
            ->toThrow(RuntimeException::class, 'già sotto richiamo');
    });

    it('non ci si richiama da soli', function () {
        $dm = User::factory()->dm()->create();

        expect(fn () => app(IssueWarning::class)->handle($dm, $dm, 'Mah.'))
            ->toThrow(RuntimeException::class);
    });

    it('gli admin non si richiamano: non giocano', function () {
        $admin = User::factory()->admin()->create();

        expect(fn () => app(IssueWarning::class)->handle($admin, User::factory()->dm()->create(), 'Mah.'))
            ->toThrow(RuntimeException::class);
    });

    it('un DM che gioca invece sì', function () {
        $dmGiocatore = User::factory()->dm()->create();

        app(IssueWarning::class)->handle($dmGiocatore, User::factory()->admin()->create(), 'Anche i DM giocano.');

        expect($dmGiocatore->fresh()->isUnderWarning())->toBeTrue();
    });
});
// Revocare un richiamo chiude il periodo di supervisione senza cancellarne lo storico.
describe('togliere un richiamo', function () {
    it('chiude il periodo senza cancellare la riga', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Motivo.');

        app(LiftWarning::class)->handle($warning, User::factory()->dm()->create(), 'Si è comportato bene.');

        expect($player->fresh()->isUnderWarning())->toBeFalse()
            ->and(Warning::whereKey($warning->getKey())->exists())->toBeTrue()
            ->and($warning->fresh()->lift_note)->toBe('Si è comportato bene.');
    });

    it('avvisa il giocatore', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Motivo.');

        Notification::fake();

        app(LiftWarning::class)->handle($warning, User::factory()->dm()->create());

        Notification::assertSentTo($player, WarningLifted::class);
    });

    it('non due volte', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Motivo.');

        app(LiftWarning::class)->handle($warning, User::factory()->dm()->create());

        expect(fn () => app(LiftWarning::class)->handle($warning->fresh(), User::factory()->dm()->create()))
            ->toThrow(RuntimeException::class, 'già stato tolto');
    });

    it('dopo se ne può dare un altro', function () {
        $player = User::factory()->player()->create();
        $dm = User::factory()->dm()->create();

        $primo = app(IssueWarning::class)->handle($player, $dm, 'Primo.');
        app(LiftWarning::class)->handle($primo, $dm);
        app(IssueWarning::class)->handle($player->fresh(), $dm, 'Ci è ricascato.');

        expect($player->fresh()->isUnderWarning())->toBeTrue()
            ->and($player->fresh()->warningHistory()['count'])->toBe(2);
    });
});

describe('lo storico', function () {
    it('conta i richiami e i giorni sotto controllo', function () {
        $player = User::factory()->player()->create();
        $dm = User::factory()->dm()->create();

        $vecchio = app(IssueWarning::class)->handle($player, $dm, 'Primo.');

        $vecchio->forceFill([
            'created_at' => now()->subDays(15),
            'lifted_at' => now()->subDays(5),
            'lifted_by' => $dm->getKey(),
        ])->save();

        $storico = $player->fresh()->warningHistory();

        expect($storico['count'])->toBe(1)
            ->and($storico['days'])->toBe(10);
    });

    it('conta anche quello ancora aperto', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'In corso.');
        $warning->forceFill(['created_at' => now()->subDays(3)])->save();

        expect($player->fresh()->warningHistory()['days'])->toBe(3);
    });

    it('senza richiami è tutto a zero', function () {
        expect(User::factory()->player()->create()->warningHistory())
            ->toBe(['count' => 0, 'days' => 0]);
    });
});

describe('chi può', function () {
    it('DM e admin danno richiami, i giocatori no', function () {
        expect(User::factory()->dm()->create()->can('create', Warning::class))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('create', Warning::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', Warning::class))->toBeFalse();
    });

    it('lo storico lo vedono anche i DM, non solo gli admin', function () {
        expect(User::factory()->dm()->create()->can('viewAny', Warning::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('viewAny', Warning::class))->toBeFalse();
    });

    it('il richiamato vede il proprio', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Motivo.');

        expect($player->fresh()->can('view', $warning))->toBeTrue()
            ->and(User::factory()->player()->create()->can('view', $warning))->toBeFalse();
    });

    it('nessuno cancella un richiamo', function () {
        $player = User::factory()->player()->create();
        $warning = app(IssueWarning::class)->handle($player, User::factory()->dm()->create(), 'Motivo.');

        expect(User::factory()->admin()->create()->can('delete', $warning))->toBeFalse();
    });
});
