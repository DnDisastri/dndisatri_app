<?php

declare(strict_types=1);

use App\Actions\Market\BuyFromShop;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\GrantCoins;
use App\Actions\Market\Purse;
use App\Actions\Market\ReverseTransaction;
use App\Domain\Dnd\Coin;
use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Livewire\InventoryManager;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\MarketItem;
use App\Models\User;
use Livewire\Livewire;

describe('pagare', function () {
    it('spezza una moneta e mette il resto nella borsa', function () {
        $pg = Character::factory()->create(['gp' => 1]);
        $candela = MarketItem::factory()->create(['name' => 'Candela', 'price_cp' => 5]);

        app(BuyFromShop::class)->handle($pg, $candela);

        $entry = LedgerEntry::forCharacter($pg)->latestFirst()->first();

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 0, 'gp' => 0, 'sp' => 9, 'cp' => 5])
            ->and($entry->cp_delta)->toBe(-5)
            ->and($entry->coins_delta)->toBe(['gp' => -1, 'sp' => 9, 'cp' => 5]);
    });

    it('usa il platino quando l\'oro non basta', function () {
        $pg = Character::factory()->create(['pp' => 2, 'gp' => 0]);
        $spada = MarketItem::factory()->named('Spada Lunga', 15)->create();

        app(BuyFromShop::class)->handle($pg, $spada);

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 0, 'gp' => 5, 'sp' => 0, 'cp' => 0]);
    });

    it('rifiuta se il valore della borsa non basta, e dice quanto vale', function () {
        $pg = Character::factory()->create(['gp' => 1, 'sp' => 4]);
        $spada = MarketItem::factory()->named('Spada Lunga', 15)->create();

        expect(fn () => app(BuyFromShop::class)->handle($pg, $spada))
            ->toThrow(MarketException::class, 'Servono 15 mo, ma la borsa vale 1 mo 4 ma.');
    });
});

describe('ricevere', function () {
    it('una vendita arriva scomposta in oro, argento e rame', function () {
        $venditore = Character::factory()->create(['gp' => 0]);
        $compratore = Character::factory()->create(['gp' => 20]);
        $venditore->addToInventory('Corda di Seta', valueCp: 1000);

        $annuncio = app(CreateListing::class)->handle($venditore, 'Corda di Seta', 1, 1253);
        app(BuyListing::class)->handle($annuncio, $compratore);

        expect($venditore->fresh()->coins()->toArray())->toBe(['pp' => 0, 'gp' => 12, 'sp' => 5, 'cp' => 3]);
    });

    it('le monete del DM arrivano nelle pile indicate', function () {
        $pg = Character::factory()->create(['gp' => 0]);

        app(GrantCoins::class)->give($pg, new Coins(pp: 3, cp: 7), User::factory()->dm()->create(), 'Tesoro');

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 3, 'gp' => 0, 'sp' => 0, 'cp' => 7]);
    });
});

describe('cambiare dalla scheda', function () {
    it('cambia solo in modo esatto, e lo scrive nel Registro a valore zero', function () {
        $giocatore = User::factory()->player()->create();
        $pg = Character::factory()->ownedBy($giocatore)->create(['gp' => 701]);

        Livewire::actingAs($giocatore)
            ->test(InventoryManager::class, ['character' => $pg])
            ->call('apriCambio')
            ->set('cambioDa', 'gp')
            ->set('cambioA', 'pp')
            ->set('cambioQuante', 701)
            ->call('cambia')
            ->assertHasErrors('cambioQuante')
            ->set('cambioQuante', 700)
            ->call('cambia')
            ->assertHasNoErrors();

        $entry = LedgerEntry::forCharacter($pg)->latestFirst()->first();

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 70, 'gp' => 1, 'sp' => 0, 'cp' => 0])
            ->and($entry->action)->toBe(LedgerAction::Exchange)
            ->and($entry->cp_delta)->toBe(0)
            ->and($entry->coins_delta)->toBe(['pp' => 70, 'gp' => -700]);
    });

    it('verso il basso, e non oltre le monete che si hanno', function () {
        $pg = Character::factory()->create(['pp' => 1, 'gp' => 0]);

        app(Purse::class)->convert($pg, Coin::Platinum, Coin::Silver, 1);

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 0, 'gp' => 0, 'sp' => 100, 'cp' => 0])
            ->and(fn () => app(Purse::class)->convert($pg->fresh(), Coin::Platinum, Coin::Gold, 1))
            ->toThrow(MarketException::class);
    });

    it('non lo fa un altro giocatore', function () {
        $pg = Character::factory()->create(['gp' => 100]);

        Livewire::actingAs(User::factory()->player()->create())
            ->test(InventoryManager::class, ['character' => $pg])
            ->call('apriCambio')
            ->assertForbidden();
    });
});

describe('annullare', function () {
    it('un acquisto restituisce le monete esatte, se ci sono ancora', function () {
        $pg = Character::factory()->create(['pp' => 2, 'gp' => 0]);
        $spada = MarketItem::factory()->named('Spada Lunga', 15)->create();
        app(BuyFromShop::class)->handle($pg, $spada);

        app(ReverseTransaction::class)->shopPurchase(
            LedgerEntry::forCharacter($pg)->latestFirst()->first(), User::factory()->admin()->create(), 'Prova',
        );

        expect($pg->fresh()->coins()->toArray())->toBe(['pp' => 2, 'gp' => 0, 'sp' => 0, 'cp' => 0]);
    });

    it('e il loro valore, se nel frattempo le ha cambiate', function () {
        $pg = Character::factory()->create(['pp' => 2, 'gp' => 0]);
        $spada = MarketItem::factory()->named('Spada Lunga', 15)->create();
        app(BuyFromShop::class)->handle($pg, $spada);
        app(Purse::class)->convert($pg->fresh(), Coin::Gold, Coin::Silver, 5);

        app(ReverseTransaction::class)->shopPurchase(
            LedgerEntry::forCharacter($pg)->where('action', LedgerAction::Buy)->first(), User::factory()->admin()->create(), 'Prova',
        );

        expect($pg->fresh()->purseValue())->toBe(2000)
            ->and($pg->fresh()->coins()->toArray())->toBe(['pp' => 0, 'gp' => 15, 'sp' => 50, 'cp' => 0]);
    });
});
