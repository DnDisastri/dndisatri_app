<?php

declare(strict_types=1);

use App\Actions\Market\BuyListing;
use App\Actions\Market\CancelListing;
use App\Actions\Market\CreateListing;
use App\Enums\LedgerAction;
use App\Enums\ListingStatus;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\MarketListing;
use App\Models\User;

describe('mettere in vendita', function () {
    // Un annuncio mette subito l'oggetto in deposito, impedendo vendite o scambi concorrenti.
    it('toglie subito l\'oggetto dall\'inventario', function () {

        $seller = Character::factory()->create();
        $seller->addToInventory('Pozione di Cura', 2, 'Pozioni', 50);

        app(CreateListing::class)->handle($seller, 'Pozione di Cura', 2, 120);

        expect($seller->fresh()->ownsItem('Pozione di Cura'))->toBeFalse();
    });

    it('copia nell\'annuncio i dati dell\'oggetto', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Spada Lunga', 1, 'Armi', 15, 'Affilata di recente.');

        $listing = app(CreateListing::class)->handle($seller, 'Spada Lunga', 1, 40);

        expect($listing->category)->toBe('Armi')
            ->and($listing->unit_value)->toBe(15)
            ->and($listing->details)->toBe('Affilata di recente.');
    });

    it('rifiuta di vendere ciò che non si possiede', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Corda', 1);

        expect(fn () => app(CreateListing::class)->handle($seller, 'Corda', 5, 10))
            ->toThrow(MarketException::class);

        expect($seller->fresh()->ownsItem('Corda'))->toBeTrue();
    });

    it('scrive nel Registro senza muovere oro', function () {
        $seller = Character::factory()->create(['gp' => 100]);
        $seller->addToInventory('Pozione di Cura', 1);

        app(CreateListing::class)->handle($seller, 'Pozione di Cura', 1, 60);

        $entry = LedgerEntry::forCharacter($seller)->latestFirst()->first();

        expect($entry->action)->toBe(LedgerAction::SellList)
            ->and($entry->gp_delta)->toBe(0)
            ->and($seller->fresh()->gp)->toBe(100);
    });
});

describe('ritirare un annuncio', function () {
    it('restituisce l\'oggetto al venditore', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Pozione di Cura', 3, 'Pozioni', 50);
        $listing = app(CreateListing::class)->handle($seller, 'Pozione di Cura', 3, 150);

        app(CancelListing::class)->handle($listing);

        expect($seller->fresh()->ownsItem('Pozione di Cura', 3))->toBeTrue()
            ->and($listing->fresh()->status)->toBe(ListingStatus::Cancelled);
    });

    it('non si ritira due volte', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Corda', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Corda', 1, 10);

        app(CancelListing::class)->handle($listing);

        expect(fn () => app(CancelListing::class)->handle($listing->fresh()))
            ->toThrow(MarketException::class);

        expect($seller->fresh()->items()->where('name', 'Corda')->sum('qty'))->toBe(1);
    });
});

describe('comprare da un giocatore', function () {
    it('sposta oro e oggetto fra i due', function () {
        $seller = Character::factory()->create(['gp' => 10]);
        $buyer = Character::factory()->create(['gp' => 200]);
        $seller->addToInventory('Pozione di Cura', 1, 'Pozioni', 50);
        $listing = app(CreateListing::class)->handle($seller, 'Pozione di Cura', 1, 80);

        app(BuyListing::class)->handle($listing, $buyer);

        expect($buyer->fresh()->gp)->toBe(120)
            ->and($seller->fresh()->gp)->toBe(90)
            ->and($buyer->fresh()->ownsItem('Pozione di Cura'))->toBeTrue()
            ->and($listing->fresh()->status)->toBe(ListingStatus::Sold)
            ->and($listing->fresh()->buyer_character_id)->toBe($buyer->id);
    });

    it('scrive due righe nel Registro, una per parte', function () {
        $seller = Character::factory()->create(['gp' => 0]);
        $buyer = Character::factory()->create(['gp' => 100]);
        $seller->addToInventory('Corda', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Corda', 1, 30);

        app(BuyListing::class)->handle($listing, $buyer);

        expect(LedgerEntry::forCharacter($buyer)->latestFirst()->first()->gp_delta)->toBe(-30)
            ->and(LedgerEntry::forCharacter($seller)->latestFirst()->first()->gp_delta)->toBe(30);
    });
// Un acquisto fallito deve lasciare annuncio, oro e inventario invariati.
    it('rifiuta se il compratore non ha abbastanza oro', function () {
        $seller = Character::factory()->create();
        $buyer = Character::factory()->create(['gp' => 10]);
        $seller->addToInventory('Spadone', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Spadone', 1, 100);

        expect(fn () => app(BuyListing::class)->handle($listing, $buyer))
            ->toThrow(MarketException::class);

        expect($listing->fresh()->isOpen())->toBeTrue()
            ->and($buyer->fresh()->gp)->toBe(10)
            ->and($buyer->fresh()->items()->count())->toBe(0);
    });

    it('non si compra due volte lo stesso annuncio', function () {
        $seller = Character::factory()->create();
        $first = Character::factory()->create(['gp' => 500]);
        $second = Character::factory()->create(['gp' => 500]);
        $seller->addToInventory('Corda', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Corda', 1, 30);

        app(BuyListing::class)->handle($listing, $first);

        expect(fn () => app(BuyListing::class)->handle($listing->fresh(), $second))
            ->toThrow(MarketException::class);

        expect($second->fresh()->gp)->toBe(500);
    });

    it('non si compra il proprio annuncio', function () {
        $seller = Character::factory()->create(['gp' => 500]);
        $seller->addToInventory('Corda', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Corda', 1, 30);

        expect(fn () => app(BuyListing::class)->handle($listing, $seller))
            ->toThrow(MarketException::class);
    });

    it('un annuncio ritirato non è più comprabile', function () {
        $seller = Character::factory()->create();
        $buyer = Character::factory()->create(['gp' => 500]);
        $seller->addToInventory('Corda', 1);
        $listing = app(CreateListing::class)->handle($seller, 'Corda', 1, 30);
        app(CancelListing::class)->handle($listing);

        expect(fn () => app(BuyListing::class)->handle($listing->fresh(), $buyer))
            ->toThrow(MarketException::class);
    });
});

describe('permessi', function () {
    it('vende e compra chi ha un personaggio vivo', function () {
        $player = User::factory()->player()->create();
        Character::factory()->ownedBy($player)->create();

        $senzaPersonaggio = User::factory()->player()->create();

        expect($player->can('create', MarketListing::class))->toBeTrue()
            ->and($senzaPersonaggio->can('create', MarketListing::class))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('create', MarketListing::class))->toBeFalse();
    });

    it('il venditore non compra il proprio annuncio ma può ritirarlo', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();
        $listing = MarketListing::factory()->soldBy($character)->create();

        expect($player->can('buy', $listing))->toBeFalse()
            ->and($player->can('cancel', $listing))->toBeTrue();
    });

    it('un altro giocatore compra ma non ritira', function () {
        $other = User::factory()->player()->create();
        Character::factory()->ownedBy($other)->create();
        $listing = MarketListing::factory()->create();

        expect($other->can('buy', $listing))->toBeTrue()
            ->and($other->can('cancel', $listing))->toBeFalse();
    });

    it('gli admin possono ritirare gli annunci altrui, per fare pulizia', function () {
        expect(User::factory()->admin()->create()->can('cancel', MarketListing::factory()->create()))
            ->toBeTrue();
    });
});
