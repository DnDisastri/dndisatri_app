<?php

declare(strict_types=1);

use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Actions\Supervision\ApproveSupervisedAction;
use App\Actions\Supervision\RejectSupervisedAction;
use App\Actions\Supervision\Supervisor;
use App\Actions\Users\IssueWarning;
use App\Actions\Users\LiftWarning;
use App\Enums\SupervisedActionType;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\SupervisedActionDecided;
use Illuminate\Support\Facades\Notification;

// Seleziona la notifica per tipo perché il richiamo e la sua decisione possono essere registrati nello stesso istante.
function decisionNotice(User $user): object
{
    return $user->notifications()->where('type', SupervisedActionDecided::class)->firstOrFail();
}

beforeEach(function () {
    $this->dm = User::factory()->dm()->create();

    $this->sorvegliato = User::factory()->player()->create();
    $this->anna = Character::factory()->ownedBy($this->sorvegliato)->create(['name' => 'Anna', 'gp' => 100]);

    $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);
});

describe('senza richiamo non cambia niente', function () {
    it('lo scambio parte subito', function () {
        $result = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        expect($result)->toBeInstanceOf(Trade::class)
            ->and(SupervisedAction::count())->toBe(0);
    });

    it('e la vendita anche', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $result = app(Supervisor::class)->createListing($this->sorvegliato, $this->anna, 'Spada Lunga', 1, 20);

        expect($result)->toBeInstanceOf(MarketListing::class);
    });
});
// Sotto richiamo l'azione viene registrata come richiesta, ma non modifica lo stato finché un DM non la approva.
describe('sotto richiamo si ferma e chiede', function () {
    beforeEach(function () {
        app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Scambio in malafede.');
        $this->sorvegliato = $this->sorvegliato->fresh();
    });

    it('la proposta di scambio non parte', function () {
        $result = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        expect($result)->toBeInstanceOf(SupervisedAction::class)
            ->and($result->type)->toBe(SupervisedActionType::TradeProposal)
            ->and(Trade::count())->toBe(0);
    });

    it('la messa in vendita nemmeno', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $result = app(Supervisor::class)->createListing($this->sorvegliato, $this->anna, 'Spada Lunga', 1, 20);

        expect($result)->toBeInstanceOf(SupervisedAction::class)
            ->and(MarketListing::count())->toBe(0)
            ->and($this->anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });

    it('nemmeno accettare uno scambio ricevuto', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->bruno, to: $this->anna, giveGp: 20);

        $result = app(Supervisor::class)->acceptTrade($this->sorvegliato, $trade);

        expect($result)->toBeInstanceOf(SupervisedAction::class)
            ->and($this->anna->fresh()->gp)->toBe(100);
    });

    it('nemmeno comprare da un annuncio', function () {
        $this->bruno->addToInventory('Scudo', value: 10);
        $listing = app(CreateListing::class)->handle($this->bruno, 'Scudo', 1, 15);

        $result = app(Supervisor::class)->buyListing($this->sorvegliato, $listing, $this->anna);

        expect($result)->toBeInstanceOf(SupervisedAction::class)
            ->and($this->anna->fresh()->gp)->toBe(100);
    });

    it('la richiesta ricorda sotto quale richiamo è nata', function () {
        $result = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        expect($result->warning_id)->toBe($this->sorvegliato->activeWarning()->getKey());
    });
});

describe('il negozio della gilda resta libero', function () {
    it('anche sotto richiamo si compra senza chiedere', function () {
        app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Motivo.');

        $item = App\Models\MarketItem::factory()->create(['price' => 10, 'is_unlimited' => true]);

        app(App\Actions\Market\BuyFromShop::class)->handle($this->anna, $item);

        expect(SupervisedAction::count())->toBe(0)
            ->and($this->anna->fresh()->gp)->toBe(90);
    });
});

describe('il via libera', function () {
    beforeEach(function () {
        app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Motivo.');
        $this->sorvegliato = $this->sorvegliato->fresh();
    });

    it('esegue davvero l\'operazione', function () {
        $pending = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        $trade = app(ApproveSupervisedAction::class)->handle($pending, $this->dm);

        expect($trade)->toBeInstanceOf(Trade::class)
            ->and($pending->fresh()->isPending())->toBeFalse();
    });
// L'approvazione rivalida lo stato corrente; se l'operazione non è più possibile, la richiesta resta pendente.
    it('fallisce se nel frattempo il mondo è cambiato', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $pending = app(Supervisor::class)->createListing(
            $this->sorvegliato, $this->anna, 'Spada Lunga', 1, 20,
        );

        $this->anna->removeFromInventory('Spada Lunga');

        expect(fn () => app(ApproveSupervisedAction::class)->handle($pending, $this->dm))
            ->toThrow(MarketException::class);

        expect($pending->fresh()->isPending())->toBeTrue();
    });

    it('non si decide due volte', function () {
        $pending = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        app(ApproveSupervisedAction::class)->handle($pending, $this->dm);

        expect(fn () => app(ApproveSupervisedAction::class)->handle($pending->fresh(), $this->dm))
            ->toThrow(RuntimeException::class, 'già stata decisa');
    });

    it('avvisa il giocatore', function () {
        $pending = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );

        Notification::fake();

        app(ApproveSupervisedAction::class)->handle($pending, $this->dm);

        Notification::assertSentTo($this->sorvegliato, SupervisedActionDecided::class);
    });
});

describe('il blocco', function () {
    beforeEach(function () {
        app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Motivo.');
        $this->sorvegliato = $this->sorvegliato->fresh();

        $this->pending = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );
    });

    it('vuole una spiegazione, sempre', function () {
        expect(fn () => app(RejectSupervisedAction::class)->handle($this->pending, $this->dm, '   '))
            ->toThrow(InvalidArgumentException::class, 'Serve una spiegazione');
    });

    it('non esegue niente', function () {
        app(RejectSupervisedAction::class)->handle($this->pending, $this->dm, 'Prezzo fuori mercato.');

        expect(Trade::count())->toBe(0)
            ->and($this->anna->fresh()->gp)->toBe(100);
    });

    it('e la spiegazione arriva intera al giocatore', function () {
        app(RejectSupervisedAction::class)->handle(
            $this->pending, $this->dm, 'Quello scudo vale dieci volte tanto: rifallo a prezzo giusto.',
        );

        expect(decisionNotice($this->sorvegliato)->data['body'])
            ->toBe('Quello scudo vale dieci volte tanto: rifallo a prezzo giusto.');
    });

    it('l\'avviso non dice chi ha deciso', function () {
        $dm = User::factory()->dm()->create(['name' => 'Nome Del DM']);

        app(RejectSupervisedAction::class)->handle($this->pending, $dm, 'No.');

        expect(json_encode(decisionNotice($this->sorvegliato)->data))->not->toContain('Nome Del DM');
    });
});

describe('chi decide', function () {
    beforeEach(function () {
        app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Motivo.');
        $this->sorvegliato = $this->sorvegliato->fresh();

        $this->pending = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno, giveGp: 10,
        );
    });

    it('un DM estraneo sì', function () {
        expect($this->dm->can('approve', $this->pending))->toBeTrue();
    });
// Chi è coinvolto nell'azione tramite un proprio personaggio non può anche esserne il revisore.
    it('ma non un DM con un personaggio dentro lo scambio', function () {
        $dmInteressato = User::factory()->dm()->create();
        $this->bruno->forceFill(['user_id' => $dmInteressato->getKey()])->save();

        expect($dmInteressato->can('approve', $this->pending->fresh()))->toBeFalse();
    });

    it('e nemmeno il richiamato stesso, se è un DM', function () {
        $this->sorvegliato->assignRole(User::ROLE_DM);

        expect($this->sorvegliato->fresh()->can('approve', $this->pending))->toBeFalse();
    });

    it('un giocatore qualsiasi no', function () {
        expect(User::factory()->player()->create()->can('approve', $this->pending))->toBeFalse();
    });

    it('il richiamato vede la propria richiesta', function () {
        expect($this->sorvegliato->can('view', $this->pending))->toBeTrue()
            ->and(User::factory()->player()->create()->can('view', $this->pending))->toBeFalse();
    });
});

describe('tolto il richiamo', function () {
    it('si torna a fare tutto senza chiedere', function () {
        $warning = app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Motivo.');

        app(LiftWarning::class)->handle($warning, $this->dm, 'Si è comportato bene.');

        $result = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato->fresh(), $this->anna, $this->bruno, giveGp: 10,
        );

        expect($result)->toBeInstanceOf(Trade::class);
    });
});
