<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
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

it('la sezione Utenti resta chiusa ai giocatori', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get(App\Filament\Resources\Users\UserResource::getUrl('index'))
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
