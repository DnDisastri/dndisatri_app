<?php

declare(strict_types=1);

use App\Actions\Market\BuyListing;
use App\Actions\Market\CancelListing;
use App\Actions\Market\CreateListing;
use App\Livewire\InventoryManager;
use App\Models\Character;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->io = Character::factory()->for(User::factory()->player())->create(['gp' => 100]);
    $this->altro = Character::factory()->for(User::factory()->player())->create(['gp' => 100]);
    $this->spada = $this->io->addToInventory('Spada del Nonno', valueCp: 1000);
});

it('il proprietario scrive la nota e la vede nello zaino', function () {
    Livewire::actingAs($this->io->user)
        ->test(InventoryManager::class, ['character' => $this->io])
        ->call('apriNota', $this->spada->id)
        ->set('nota', 'Trovata nella cripta di Valmora.')
        ->call('salvaNota')
        ->assertSee('Trovata nella cripta di Valmora.');

    expect($this->spada->fresh()->notes)->toBe('Trovata nella cripta di Valmora.');
});

it('una nota vuota si cancella', function () {
    $this->spada->forceFill(['notes' => 'vecchia'])->save();

    Livewire::actingAs($this->io->user)
        ->test(InventoryManager::class, ['character' => $this->io])
        ->call('apriNota', $this->spada->id)
        ->set('nota', '   ')
        ->call('salvaNota');

    expect($this->spada->fresh()->notes)->toBeNull();
});

it('un DM non la scrive al posto del giocatore', function () {
    Livewire::actingAs(User::factory()->dm()->create())
        ->test(InventoryManager::class, ['character' => $this->io])
        ->call('apriNota', $this->spada->id)
        ->assertForbidden();
});

it('chi compra l\'oggetto non riceve la nota', function () {
    $this->spada->forceFill(['notes' => 'Ricordo di famiglia'])->save();

    $annuncio = app(CreateListing::class)->handle($this->io, 'Spada del Nonno', 1, 500);
    app(BuyListing::class)->handle($annuncio, $this->altro);

    expect($this->altro->items()->where('name', 'Spada del Nonno')->first()->notes)->toBeNull();
});

it('ritirando l\'annuncio la nota torna al venditore', function () {
    $this->spada->forceFill(['notes' => 'Ricordo di famiglia'])->save();

    $annuncio = app(CreateListing::class)->handle($this->io, 'Spada del Nonno', 1, 500);
    app(CancelListing::class)->handle($annuncio);

    expect($this->io->items()->where('name', 'Spada del Nonno')->first()->notes)->toBe('Ricordo di famiglia');
});
