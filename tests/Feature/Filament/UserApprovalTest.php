<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ListUsers;
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
