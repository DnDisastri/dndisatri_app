<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

it('agli ospiti mostra la presentazione', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Tiriamo i Dadi')
        ->assertDontSee('Navigazione principale');
});

it('mostra la Home a chi è entrato', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Bentornato');
});

it('non parla del personaggio nella Home', function () {
    $giocatore = User::factory()->player()->create();
    Character::factory()->ownedBy($giocatore)->create(['name' => 'Brolan']);

    $this->actingAs($giocatore)
        ->get('/')
        ->assertOk()
        ->assertDontSee('Brolan')
        ->assertDontSee('Non hai ancora un personaggio');
});

it('espone il pannello di gestione', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
