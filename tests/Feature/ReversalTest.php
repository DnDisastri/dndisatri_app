<?php

declare(strict_types=1);

use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyFromShop;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Actions\Market\GrantGold;
use App\Actions\Market\ReverseTransaction;
use App\Enums\LedgerAction;
use App\Exceptions\ReversalException;
use App\Models\Character;
use App\Models\MarketItem;
use App\Models\User;
use App\Notifications\TransactionReversed;
use Illuminate\Support\Facades\Notification;

// L'annullamento ripristina una transazione conclusa solo se beni e oro sono ancora recuperabili.
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->anna = Character::factory()->create(['name' => 'Anna', 'gp' => 100]);
    $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);
});

describe('uno scambio', function () {
    it('rimette oggetti e oro da dove erano partiti', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $this->bruno->addToInventory('Scudo', value: 10);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno,
            give: [['name' => 'Spada Lunga']], want: [['name' => 'Scudo']],
            giveGp: 30,
        );
        app(AcceptTrade::class)->handle($trade);

        expect($this->anna->fresh()->gp)->toBe(70);

        app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Scambio in malafede.');

        expect($this->anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($this->anna->fresh()->ownsItem('Scudo'))->toBeFalse()
            ->and($this->bruno->fresh()->ownsItem('Scudo'))->toBeTrue()
            ->and($this->anna->fresh()->gp)->toBe(100)
            ->and($this->bruno->fresh()->gp)->toBe(100);
    });

    it('si rifiuta se l\'oggetto è stato rivenduto, e dice cosa manca', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );
        app(AcceptTrade::class)->handle($trade);

        $this->bruno->removeFromInventory('Spada Lunga');

        expect(fn () => app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Truffa.'))
            ->toThrow(ReversalException::class, 'Spada Lunga');
    });

    it('si rifiuta se l\'oro è stato speso', function () {
        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, giveGp: 100,
        );
        app(AcceptTrade::class)->handle($trade);

        $this->bruno->fresh()->decrement('gp', 150);

        expect(fn () => app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Truffa.'))
            ->toThrow(ReversalException::class, 'sotto zero');
    });
// Tutte le precondizioni vengono verificate prima di muovere beni o oro.
    it('non muove niente quando si rifiuta', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno,
            give: [['name' => 'Spada Lunga']], giveGp: 50,
        );
        app(AcceptTrade::class)->handle($trade);
        $this->bruno->removeFromInventory('Spada Lunga');

        try {
            app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Truffa.');
        } catch (ReversalException) {
        }

        expect($this->anna->fresh()->gp)->toBe(50)
            ->and($this->bruno->fresh()->gp)->toBe(150);
    });

    it('una volta sola', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);
        app(AcceptTrade::class)->handle($trade);

        app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Motivo.');

        expect(fn () => app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Ancora.'))
            ->toThrow(ReversalException::class, 'già stata annullata');
    });

    it('e non su uno mai accettato', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);

        expect(fn () => app(ReverseTransaction::class)->trade($trade, $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class, 'andati a buon fine');
    });

    it('avvisa tutti e due i giocatori', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);
        app(AcceptTrade::class)->handle($trade);

        Notification::fake();

        app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Scambio in malafede.');

        Notification::assertSentTo($this->anna->user, TransactionReversed::class);
        Notification::assertSentTo($this->bruno->user, TransactionReversed::class);
    });
// L'annullamento aggiunge movimenti compensativi al Registro senza riscrivere lo storico.
    it('lascia traccia nel Registro invece di riscriverlo', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);
        app(AcceptTrade::class)->handle($trade);

        $primaDelle = $this->anna->ledgerEntries()->count();

        app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Motivo.');

        expect($this->anna->ledgerEntries()->count())->toBeGreaterThan($primaDelle)
            ->and($this->anna->ledgerEntries()->where('action', LedgerAction::Reversal)->exists())->toBeTrue();
    });
});

describe('una vendita fra giocatori', function () {
    it('rende l\'oggetto al venditore e l\'oro al compratore', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($this->anna, 'Spada Lunga', 1, 40);
        app(BuyListing::class)->handle($listing, $this->bruno);

        expect($this->anna->fresh()->gp)->toBe(140);

        app(ReverseTransaction::class)->listingSale($listing->fresh(), $this->admin, 'Prezzo gonfiato.');

        expect($this->anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($this->bruno->fresh()->ownsItem('Spada Lunga'))->toBeFalse()
            ->and($this->anna->fresh()->gp)->toBe(100)
            ->and($this->bruno->fresh()->gp)->toBe(100);
    });

    it('si rifiuta se il compratore non ha più l\'oggetto', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($this->anna, 'Spada Lunga', 1, 40);
        app(BuyListing::class)->handle($listing, $this->bruno);

        $this->bruno->fresh()->removeFromInventory('Spada Lunga');

        expect(fn () => app(ReverseTransaction::class)->listingSale($listing->fresh(), $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class);
    });

    it('e non su un annuncio ancora aperto', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($this->anna, 'Spada Lunga', 1, 40);

        expect(fn () => app(ReverseTransaction::class)->listingSale($listing, $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class, 'non è stato venduto');
    });
});

describe('un acquisto dal negozio', function () {
    it('rende l\'oro, toglie l\'oggetto e ripristina le scorte', function () {
        $item = MarketItem::factory()->create(['name' => 'Corda', 'price' => 10, 'stock' => 5, 'is_unlimited' => false]);

        app(BuyFromShop::class)->handle($this->anna, $item, 2);

        expect($this->anna->fresh()->gp)->toBe(80)
            ->and($item->fresh()->stock)->toBe(3);

        $entry = $this->anna->ledgerEntries()->where('action', LedgerAction::Buy)->latest('id')->first();

        app(ReverseTransaction::class)->shopPurchase($entry, $this->admin, 'Comprato per sbaglio.');

        expect($this->anna->fresh()->gp)->toBe(100)
            ->and($this->anna->fresh()->ownsItem('Corda'))->toBeFalse()
            ->and($item->fresh()->stock)->toBe(5);
    });

    it('si rifiuta se l\'oggetto non c\'è più', function () {
        $item = MarketItem::factory()->create(['name' => 'Corda', 'price' => 10, 'is_unlimited' => true]);
        app(BuyFromShop::class)->handle($this->anna, $item);

        $this->anna->fresh()->removeFromInventory('Corda');

        $entry = $this->anna->ledgerEntries()->where('action', LedgerAction::Buy)->latest('id')->first();

        expect(fn () => app(ReverseTransaction::class)->shopPurchase($entry, $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class, 'Corda');
    });
});

describe('l\'oro assegnato da un DM', function () {
    it('torna com\'era', function () {
        app(GrantGold::class)->handle($this->anna, 500, User::factory()->dm()->create(), 'Bottino della serata');

        expect($this->anna->fresh()->gp)->toBe(600);

        $entry = $this->anna->ledgerEntries()->where('action', LedgerAction::DmGold)->latest('id')->first();

        app(ReverseTransaction::class)->goldGrant($entry, $this->admin, 'Uno zero di troppo.');

        expect($this->anna->fresh()->gp)->toBe(100);
    });

    it('si rifiuta se nel frattempo è stato speso', function () {
        app(GrantGold::class)->handle($this->anna, 500, User::factory()->dm()->create());
        $this->anna->fresh()->decrement('gp', 550);

        $entry = $this->anna->ledgerEntries()->where('action', LedgerAction::DmGold)->latest('id')->first();

        expect(fn () => app(ReverseTransaction::class)->goldGrant($entry, $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class, 'sotto zero');
    });

    it('non si annulla una riga di tipo diverso', function () {
        $item = MarketItem::factory()->create(['price' => 10, 'is_unlimited' => true]);
        app(BuyFromShop::class)->handle($this->anna, $item);

        $entry = $this->anna->ledgerEntries()->where('action', LedgerAction::Buy)->latest('id')->first();

        expect(fn () => app(ReverseTransaction::class)->goldGrant($entry, $this->admin, 'Motivo.'))
            ->toThrow(ReversalException::class, 'non è di quel tipo');
    });
});

describe('chi può annullare', function () {
    it('solo un admin, non un DM', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);
        app(AcceptTrade::class)->handle($trade);

        expect($this->admin->can('reverse', $trade->fresh()))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('reverse', $trade->fresh()))->toBeFalse();
    });

    it('e non due volte', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);
        app(AcceptTrade::class)->handle($trade);
        app(ReverseTransaction::class)->trade($trade->fresh(), $this->admin, 'Motivo.');

        expect($this->admin->can('reverse', $trade->fresh()))->toBeFalse();
    });
});

describe('bloccare quelle ancora aperte', function () {
    it('un admin ferma una proposta di scambio che non è sua', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);

        expect($this->admin->can('cancel', $trade))->toBeTrue();
    });

    it('e ritira un annuncio di chiunque', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($this->anna, 'Spada Lunga', 1, 40);

        expect($this->admin->can('cancel', $listing))->toBeTrue();
    });

    it('ma un giocatore estraneo no', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 30);

        expect(User::factory()->player()->create()->can('cancel', $trade))->toBeFalse();
    });
});
