<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

// Un account appena registrato resta in attesa finché un admin non lo approva.
it('un admin approva un iscritto in attesa dal pannello', function () {
    $admin = User::factory()->admin()->create();
    $inAttesa = User::factory()->player()->unapproved()->create();

    expect($inAttesa->isApproved())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('approva', $inAttesa);

    expect($inAttesa->refresh()->isApproved())->toBeTrue();
});

// La nomina dal pannello: l'admin decide, il giocatore non ha chiesto niente.
it('un admin nomina un giocatore dungeon master dal pannello', function () {
    $admin = User::factory()->admin()->create();
    $giocatore = User::factory()->player()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('nominaDm', $giocatore);

    expect($giocatore->refresh()->isDm())->toBeTrue();
});

// I DM stanno nella loro scheda: la revoca si fa da lì.
it('e gli toglie il ruolo riportandolo giocatore', function () {
    $admin = User::factory()->admin()->create();
    $dm = User::factory()->dm()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->set('activeTab', 'dm')
        ->callTableAction('revocaDm', $dm);

    expect($dm->refresh()->isDm())->toBeFalse();
});

it('a un DM le due azioni non compaiono nemmeno', function () {
    $dm = User::factory()->dm()->create();
    $giocatore = User::factory()->player()->create();

    Livewire::actingAs($dm)
        ->test(ListUsers::class)
        ->assertTableActionHidden('nominaDm', $giocatore);
});

it('la revoca avvisa invece di fallire quando la campagna è ancora aperta', function () {
    $admin = User::factory()->admin()->create();
    $dm = User::factory()->dm()->create();
    Campaign::factory()->runBy($dm)->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->set('activeTab', 'dm')
        ->callTableAction('revocaDm', $dm)
        ->assertNotified();

    expect($dm->refresh()->isDm())->toBeTrue();
});

it('la sezione Utenti resta chiusa ai giocatori', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

it('il badge del menu Utenti conta gli iscritti in attesa', function () {
    User::factory()->player()->unapproved()->count(2)->create();
    User::factory()->player()->create();

    expect(UserResource::getNavigationBadge())->toBe('2');
});

it('senza iscritti in attesa il badge non compare', function () {
    User::factory()->player()->count(3)->create();

    expect(UserResource::getNavigationBadge())->toBeNull();
});
