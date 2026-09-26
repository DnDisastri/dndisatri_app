<?php

declare(strict_types=1);

use App\Enums\PendingChangeType;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Models\PendingChange;
use App\Models\User;

it('mostra il titolo leggibile invece dell\'id', function () {
    $change = PendingChange::factory()->create(['type' => PendingChangeType::CharacterEdit]);

    $this->actingAs(User::factory()->dm()->create())
        ->get(PendingChangeResource::getUrl('view', ['record' => $change]))
        ->assertOk()
        ->assertSee('Richiesta modifica scheda');
});

it('rende la pagina di un bottino con i suoi oggetti', function () {
    $change = PendingChange::factory()->create([
        'type' => PendingChangeType::Loot,
        'grant_gp' => 40,
        'grant_items' => [['name' => 'Spadone lunghissimo', 'qty' => 1]],
        'summary' => 'Bottino: 40 mo e 1× Spadone lunghissimo',
    ]);

    $this->actingAs(User::factory()->dm()->create())
        ->get(PendingChangeResource::getUrl('view', ['record' => $change]))
        ->assertOk()
        ->assertSee('Spadone lunghissimo')
        ->assertSee('Bottino');
});
