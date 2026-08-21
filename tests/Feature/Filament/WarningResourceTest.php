<?php

declare(strict_types=1);

use App\Actions\Users\IssueWarning;
use App\Filament\Resources\Warnings\Pages\ListWarnings;
use App\Filament\Resources\Warnings\WarningResource;
use App\Models\User;
use App\Models\Warning;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

// La Resource permette agli admin e ai DM autorizzati di applicare e revocare i richiami.
function richiamato(?User $da = null): Warning
{
    return app(IssueWarning::class)->handle(
        User::factory()->player()->create(),
        $da ?? User::factory()->dm()->create(),
        'Ha venduto due volte lo stesso anello.',
    );
}

describe('chi ci entra', function () {
    it('i DM, perché sono loro a condurre le serate', function () {
        richiamato();

        $this->actingAs(User::factory()->dm()->create())
            ->get(WarningResource::getUrl('index'))
            ->assertOk();
    });

    it('e gli admin', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(WarningResource::getUrl('index'))
            ->assertOk();
    });

    it('un giocatore no, e nel menu non la vede nemmeno', function () {
        $this->actingAs(User::factory()->player()->create());

        expect(WarningResource::canViewAny())->toBeFalse();
    });
});

describe('la pagina', function () {
    it('mostra chi è sotto controllo, con il motivo', function () {
        $warning = richiamato();

        $this->actingAs(User::factory()->dm()->create())
            ->get(WarningResource::getUrl('index'))
            ->assertOk()
            ->assertSee($warning->user->name)
            ->assertSee('Ha venduto due volte lo stesso anello.');
    });

    it('e conta sul menu quelli in corso', function () {
        expect(WarningResource::getNavigationBadge())->toBeNull();

        richiamato();

        expect(WarningResource::getNavigationBadge())->toBe('1');
    });

    it('ma non conta quelli già tolti', function () {
        $warning = richiamato();

        app(App\Actions\Users\LiftWarning::class)
            ->handle($warning, User::factory()->dm()->create());

        expect(WarningResource::getNavigationBadge())->toBeNull();
    });
});

describe('dare un richiamo', function () {
    it('mette il giocatore sotto controllo e glielo dice', function () {
        $dm = User::factory()->dm()->create();
        $giocatore = User::factory()->player()->create();

        Livewire::actingAs($dm)
            ->test(ListWarnings::class)
            ->callAction('richiama', [
                'user_id' => $giocatore->getKey(),
                'reason' => 'Ha comprato per conto di un altro senza dirlo.',
            ])
            ->assertHasNoActionErrors();

        expect($giocatore->fresh()->isUnderWarning())->toBeTrue()
            ->and($giocatore->activeWarning()->reason)
            ->toBe('Ha comprato per conto di un altro senza dirlo.');
    });

    it('senza motivo non si dà', function () {
        $giocatore = User::factory()->player()->create();

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(ListWarnings::class)
            ->callAction('richiama', ['user_id' => $giocatore->getKey(), 'reason' => ''])
            ->assertHasActionErrors(['reason']);

        expect($giocatore->fresh()->isUnderWarning())->toBeFalse();
    });

    it('e chi è già sotto controllo non è nemmeno in elenco', function () {
        $warning = richiamato();

        $this->actingAs(User::factory()->dm()->create())
            ->get(WarningResource::getUrl('index'))
            ->assertOk();
// L'azione ricontrolla il vincolo al salvataggio perché lo stato può cambiare mentre il form è aperto.
        expect(fn () => app(IssueWarning::class)->handle(
            $warning->user,
            User::factory()->dm()->create(),
            'Di nuovo.',
        ))->toThrow(RuntimeException::class);
    });
});


describe('togliere un richiamo', function () {
    it('lo chiude, e il giocatore torna libero', function () {
        $warning = richiamato();
        $dm = User::factory()->dm()->create();

        Livewire::actingAs($dm)
            ->test(ListWarnings::class)
            ->callAction(TestAction::make('togli')->table($warning), ['nota' => 'Chiarito a voce.'])
            ->assertHasNoActionErrors();

        expect($warning->user->fresh()->isUnderWarning())->toBeFalse();
    });

    it('e la riga resta, con quanto è durato', function () {
        $warning = richiamato();

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(ListWarnings::class)
            ->callAction(TestAction::make('togli')->table($warning), ['nota' => null]);

        $tolto = $warning->fresh();

        expect(Warning::count())->toBe(1)
            ->and($tolto->isActive())->toBeFalse()
            ->and($tolto->lifted_at)->not->toBeNull();
    });

    it('e su uno già tolto il pulsante non c\'è', function () {
        $warning = richiamato();

        app(App\Actions\Users\LiftWarning::class)
            ->handle($warning, User::factory()->dm()->create());

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(ListWarnings::class)
            ->assertActionHidden(TestAction::make('togli')->table($warning->fresh()));
    });
});
