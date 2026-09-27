<?php

declare(strict_types=1);

use App\Actions\Market\GrantGold;
use App\Livewire\Market\Listings;
use App\Livewire\Market\Trades;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

// Le cifre in oro stanno in colonne unsignedInteger: oltre il tetto il
// database rifiuta la scrittura, e senza questi controlli la pagina muore.
beforeEach(function () {
    $this->io = Character::factory()->for(User::factory()->player())->create(['name' => 'Grimm', 'gp' => 100]);
    $this->altro = Character::factory()->for(User::factory()->player())->create(['name' => 'Vex', 'gp' => 100]);
});

describe('le proposte di scambio', function () {
    it('rifiutano una cifra chiesta oltre il tetto', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('wantGp', Character::MAX_GP + 1)
            ->call('propose')
            ->assertHasErrors(['wantGp' => 'max']);

        expect(Trade::count())->toBe(0);
    });

    it('rifiutano una cifra offerta negativa', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('giveGp', -5)
            ->call('propose')
            ->assertHasErrors(['giveGp' => 'min']);

        expect(Trade::count())->toBe(0);
    });

    it('accettano una cifra esattamente al tetto', function () {
        Livewire::actingAs($this->io->user)
            ->test(Trades::class)
            ->set('toCharacterId', $this->altro->id)
            ->set('wantGp', Character::MAX_GP)
            ->call('propose')
            ->assertHasNoErrors();
    });
});

describe('gli annunci', function () {
    it('rifiutano un prezzo oltre il tetto', function () {
        $this->io->addToInventory('Corda di Seta', value: 10);

        Livewire::actingAs($this->io->user)
            ->test(Listings::class)
            ->set('itemName', 'Corda di Seta')
            ->set('sellQty', 1)
            ->set('price', Character::MAX_GP + 1)
            ->call('sell')
            ->assertHasErrors(['price' => 'max']);

        expect(MarketListing::soldBy($this->io)->count())->toBe(0);
    });
});

describe('l\'oro assegnato dal DM', function () {
    it('si ferma al tetto invece di far fallire la scrittura', function () {
        $dm = User::factory()->dm()->create();

        $aggiornato = app(GrantGold::class)->handle($this->io, Character::MAX_GP, $dm, 'Tesoro del drago');

        expect($aggiornato->gp)->toBe(Character::MAX_GP);
    });

    it('continua a non mandare il saldo sotto zero', function () {
        $dm = User::factory()->dm()->create();

        $aggiornato = app(GrantGold::class)->handle($this->io, -500, $dm, 'Multa');

        expect($aggiornato->gp)->toBe(0);
    });
});
