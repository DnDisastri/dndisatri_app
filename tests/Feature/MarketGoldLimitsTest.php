<?php

declare(strict_types=1);

use App\Actions\Market\GrantCoins;
use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Livewire\Market\Listings;
use App\Livewire\Market\Trades;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

// Pile e prezzi stanno in colonne unsignedInteger: oltre il tetto il database
// rifiuta la scrittura, e senza questi controlli la pagina muore.
beforeEach(function () {
    $this->io = Character::factory()->for(User::factory()->player())->create(['name' => 'Grimm', 'gp' => 100]);
    $this->altro = Character::factory()->for(User::factory()->player())->create(['name' => 'Vex', 'gp' => 100]);
});

describe('le proposte di scambio', function () {
    it('rifiutano una pila chiesta oltre il tetto', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('wantMonete.gp', Coins::MAX + 1)
            ->call('propose')
            ->assertHasErrors(['wantMonete.gp' => 'max']);

        expect(Trade::count())->toBe(0);
    });

    it('rifiutano un valore oltre il tetto, anche con pile valide', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('wantMonete.pp', Coins::MAX)
            ->call('propose')
            ->assertHasErrors('scambio');

        expect(Trade::count())->toBe(0);
    });

    it('rifiutano una cifra offerta negativa', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('giveMonete.gp', -5)
            ->call('propose')
            ->assertHasErrors(['giveMonete.gp' => 'min']);

        expect(Trade::count())->toBe(0);
    });

    it('accettano un valore esattamente al tetto', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('wantMonete.cp', Coins::MAX)
            ->call('propose')
            ->assertHasNoErrors();
    });
});

describe('gli annunci', function () {
    it('rifiutano una pila oltre il tetto', function () {
        $this->io->addToInventory('Corda di Seta', valueCp: 1000);

        Livewire::actingAs($this->io->user)
            ->test(Listings::class)
            ->set('itemName', 'Corda di Seta')
            ->set('sellQty', 1)
            ->set('price.gp', Coins::MAX + 1)
            ->call('sell')
            ->assertHasErrors(['price.gp' => 'max']);

        expect(MarketListing::soldBy($this->io)->count())->toBe(0);
    });

    it('rifiutano un prezzo il cui valore supera il tetto', function () {
        $this->io->addToInventory('Corda di Seta', valueCp: 1000);

        Livewire::actingAs($this->io->user)
            ->test(Listings::class)
            ->set('itemName', 'Corda di Seta')
            ->set('sellQty', 1)
            ->set('price.pp', Coins::MAX)
            ->call('sell')
            ->assertHasErrors('price');

        expect(MarketListing::soldBy($this->io)->count())->toBe(0);
    });
});

describe('le monete date dal DM', function () {
    it('arrivano fino al tetto della pila', function () {
        $dm = User::factory()->dm()->create();

        $aggiornato = app(GrantCoins::class)->give($this->io, new Coins(gp: Coins::MAX - 100), $dm, 'Tesoro del drago');

        expect($aggiornato->gp)->toBe(Coins::MAX);
    });

    it('oltre il tetto si rifiutano invece di far fallire la scrittura', function () {
        $dm = User::factory()->dm()->create();

        expect(fn () => app(GrantCoins::class)->give($this->io, new Coins(gp: Coins::MAX), $dm, 'Troppo'))
            ->toThrow(MarketException::class);

        expect($this->io->fresh()->gp)->toBe(100);
    });

    it('non mandano la borsa sotto zero', function () {
        $dm = User::factory()->dm()->create();

        expect(fn () => app(GrantCoins::class)->take($this->io, new Coins(gp: 500), $dm, 'Multa'))
            ->toThrow(MarketException::class);

        expect($this->io->fresh()->gp)->toBe(100);
    });
});
