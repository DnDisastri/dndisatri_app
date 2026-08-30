<?php

declare(strict_types=1);

use App\Actions\Market\CreateTradeRequest;
use App\Actions\Users\IssueWarning;
use App\Enums\TradeStatus;
use App\Livewire\InventoryManager;
use App\Livewire\Market\Trades;
use App\Models\Character;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;
use App\Notifications\TradeRequested;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;


beforeEach(function () {
    $this->io = Character::factory()->for(User::factory()->player())->create(['name' => 'Grimm', 'gp' => 100]);
    $this->altro = Character::factory()->for(User::factory()->player())->create(['name' => 'Vex', 'gp' => 100]);
});
// La vetrina espone solo gli oggetti dichiarati scambiabili; il resto può essere richiesto a parole.
describe('la vetrina', function () {
    it('di suo non mostra niente', function () {
        $this->altro->addToInventory('Amuleto di Salute', value: 500);

        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->assertDontSee('Amuleto di Salute')
            ->assertSee('Non ha messo niente in vetrina');
    });

    it('mostra solo quello che il proprietario ci ha messo', function () {
        $this->altro->addToInventory('Amuleto di Salute', value: 500);
        $corda = $this->altro->addToInventory('Corda di Seta', value: 10);
        $corda->forceFill(['tradeable' => true])->save();

        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->assertSee('Corda di Seta')
            ->assertDontSee('Amuleto di Salute');
    });

    it('l\'interruttore la mette e la toglie', function () {
        $item = $this->io->addToInventory('Corda di Seta', value: 10);

        Livewire::actingAs($this->io->user)
            ->test(InventoryManager::class, ['character' => $this->io])
            ->call('toggleTradeable', $item->id);

        expect($item->fresh()->tradeable)->toBeTrue();

        Livewire::actingAs($this->io->user)
            ->test(InventoryManager::class, ['character' => $this->io])
            ->call('toggleTradeable', $item->id);

        expect($item->fresh()->tradeable)->toBeFalse();
    });
// La disponibilità in vetrina esprime la volontà del proprietario e non può essere impostata dal DM per conto suo.
    it('e un DM non la decide al posto del giocatore', function () {
        $item = $this->io->addToInventory('Corda di Seta', value: 10);
        $dm = User::factory()->dm()->create();

        Livewire::actingAs($dm)
            ->test(InventoryManager::class, ['character' => $this->io])
            ->call('toggleTradeable', $item->id)
            ->assertForbidden();

        expect($item->fresh()->tradeable)->toBeFalse();
    });
});

describe('chiedere a parole', function () {
    it('parte una richiesta, e non uno scambio', function () {
        $this->io->addToInventory('Corda di Seta', value: 10);

        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('give', ['Corda di Seta'])
            ->set('chiedo', 'Amuleto di Salute')
            ->call('propose')
            ->assertHasNoErrors();

        expect(TradeRequest::count())->toBe(1)
            ->and(Trade::count())->toBe(0);

        $richiesta = TradeRequest::first();

        expect($richiesta->wanted)->toBe('Amuleto di Salute')
            ->and($richiesta->offeredNames()->all())->toBe(['Corda di Seta'])
            ->and($richiesta->to_character_id)->toBe($this->altro->getKey());
    });

    it('e chi la riceve lo viene a sapere', function () {
        Notification::fake();
        $this->io->addToInventory('Corda di Seta', value: 10);

        app(CreateTradeRequest::class)->handle(
            $this->io->fresh(), $this->altro, 'Amuleto di Salute', ['Corda di Seta'],
        );

        Notification::assertSentTo($this->altro->user, TradeRequested::class);
    });


    it('ma non insieme a una spunta dalla vetrina', function () {
        $corda = $this->altro->addToInventory('Corda di Seta', value: 10);
        $corda->forceFill(['tradeable' => true])->save();
        $this->io->addToInventory('Scudo', value: 10);

        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('give', ['Scudo'])
            ->set('want', ['Corda di Seta'])
            ->set('chiedo', 'Amuleto di Salute')
            ->call('propose')
            ->assertHasErrors('scambio');

        expect(TradeRequest::count())->toBe(0)
            ->and(Trade::count())->toBe(0);
    });

    it('e senza offrire niente non si chiede', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('chiedo', 'Amuleto di Salute')
            ->call('propose')
            ->assertHasErrors('scambio');

        expect(TradeRequest::count())->toBe(0);
    });

    it('e non si offre quello che non si ha', function () {
        expect(fn () => app(CreateTradeRequest::class)->handle(
            $this->io, $this->altro, 'Amuleto', ['Spada che non ho'],
        ))->toThrow(App\Exceptions\MarketException::class);
    });
});

describe('rispondere a una richiesta', function () {
    beforeEach(function () {
        $this->io->addToInventory('Corda di Seta', value: 10);
        $this->altro->addToInventory('Amuleto di Salute', value: 500);

        $this->richiesta = app(CreateTradeRequest::class)->handle(
            $this->io->fresh(), $this->altro, 'Amuleto di Salute', ['Corda di Seta'],
        );
    });

// Rispondere "ce l'ho" crea una proposta inversa: lo scambio si conclude solo dopo la conferma dell'altro giocatore.
    it('«ce l\'ho» fa nascere una proposta a parti invertite', function () {
        Livewire::actingAs($this->altro->user)
            ->test(Trades::class)
            ->call('apriRichiesta', $this->richiesta->id)
            ->set('offro', ['Amuleto di Salute'])
            ->call('accettaRichiesta')
            ->assertHasNoErrors()
            ->assertSet('richiestaAperta', null);

        $trade = Trade::sole();

        expect($trade->from_character_id)->toBe($this->altro->getKey())
            ->and($trade->to_character_id)->toBe($this->io->getKey())
            ->and($trade->givenItems()->pluck('name')->all())->toBe(['Amuleto di Salute'])
            ->and($trade->wantedItems()->pluck('name')->all())->toBe(['Corda di Seta'])
            ->and($this->richiesta->fresh()->status)->toBe(TradeStatus::Accepted)
            ->and($this->richiesta->fresh()->trade_id)->toBe($trade->getKey());
    });

    it('e la roba si muove solo quando l\'altro conferma', function () {
        Livewire::actingAs($this->altro->user)
            ->test(Trades::class)
            ->call('apriRichiesta', $this->richiesta->id)
            ->set('offro', ['Amuleto di Salute'])
            ->call('accettaRichiesta');

        expect($this->io->fresh()->ownsItem('Amuleto di Salute'))->toBeFalse();

        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->call('accept', Trade::sole()->id)
            ->assertHasNoErrors();

        expect($this->io->fresh()->ownsItem('Amuleto di Salute'))->toBeTrue()
            ->and($this->altro->fresh()->ownsItem('Corda di Seta'))->toBeTrue();
    });

    it('«non ce l\'ho» la chiude senza creare niente', function () {
        Livewire::actingAs($this->altro->user)
            ->test(Trades::class)
            ->call('rifiutaRichiesta', $this->richiesta->id);

        expect($this->richiesta->fresh()->status)->toBe(TradeStatus::Rejected)
            ->and(Trade::count())->toBe(0);
    });

    it('e chi l\'ha fatta la può ritirare', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->call('ritiraRichiesta', $this->richiesta->id);

        expect($this->richiesta->fresh()->status)->toBe(TradeStatus::Cancelled);
    });

    it('ma non risponde chi non c\'entra', function () {
        $terzo = Character::factory()->for(User::factory()->player())->create();

        Livewire::actingAs($terzo->user)
            ->test(Trades::class)
            ->call('rifiutaRichiesta', $this->richiesta->id)
            ->assertForbidden();

        expect($this->richiesta->fresh()->status)->toBe(TradeStatus::Pending);
    });

    it('e una richiesta chiusa non si riapre', function () {
        Livewire::actingAs($this->altro->user)
            ->test(Trades::class)
            ->call('rifiutaRichiesta', $this->richiesta->id)
            ->call('rifiutaRichiesta', $this->richiesta->id)
            ->assertForbidden();
    });
// La richiesta non muove beni; se genera una proposta sotto richiamo, è la proposta a passare dalla supervisione.
    it('sotto richiamo, la proposta che ne nasce resta in attesa', function () {
        app(IssueWarning::class)->handle(
            $this->altro->user, User::factory()->dm()->create(), 'Prova',
        );

        Livewire::actingAs($this->altro->user)
            ->test(Trades::class)
            ->call('apriRichiesta', $this->richiesta->id)
            ->set('offro', ['Amuleto di Salute'])
            ->call('accettaRichiesta');

        expect(SupervisedAction::pending()->count())->toBe(1)
            ->and(Trade::count())->toBe(0)
            ->and($this->richiesta->fresh()->status)->toBe(TradeStatus::Accepted)
            ->and($this->richiesta->fresh()->trade_id)->toBeNull();
    });
});
