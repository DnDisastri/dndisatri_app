<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\User;

// Gli archivi crescono a ogni serata: il Libro Mastro li divide in pagine.
it('divide in pagine le serate del Libro Mastro', function () {
    $campagna = Campaign::factory()->create();

    foreach (range(1, 8) as $i) {
        GameSession::factory()->inCampaign($campagna)->playedOn(now()->subDays($i))
            ->create(['title' => "Serata numero {$i}", 'recap' => 'Com\'è andata.']);
    }

    $utente = User::factory()->player()->create();

    $this->actingAs($utente)->get(route('ledger.index'))
        ->assertOk()
        ->assertSee('Serata numero 6')
        ->assertDontSee('Serata numero 7')
        ->assertSee('Più vecchie');

    $this->actingAs($utente)->get(route('ledger.index', ['serate' => 2]))
        ->assertOk()
        ->assertSee('Serata numero 7')
        ->assertDontSee('Serata numero 6')
        ->assertSee('Più recenti');
});

it('mostra solo le ultime serate nella campagna e rimanda al Libro Mastro', function () {
    $campagna = Campaign::factory()->create();

    foreach (range(1, 8) as $i) {
        GameSession::factory()->inCampaign($campagna)->playedOn(now()->subDays($i))
            ->create(['title' => "Serata numero {$i}"]);
    }

    $this->actingAs(User::factory()->player()->create())
        ->get(route('campaigns.show', $campagna))
        ->assertOk()
        ->assertSee('Serata numero 6')
        ->assertDontSee('Serata numero 7')
        ->assertSee('Tutti i resoconti nel Libro Mastro');
});
