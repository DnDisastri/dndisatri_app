<?php

declare(strict_types=1);

use App\Models\User;

// Mario@x e mario@x sono due indirizzi diversi. Su MySQL lo garantisce la collation della migrazione; SQLite lo è già.

it('registra un\'email che differisce da un\'altra solo per le maiuscole', function () {
    User::factory()->player()->create(['email' => 'mario@dndisastri.test']);

    $this->post(route('register'), [
        'name' => 'Mario Maiuscolo',
        'email' => 'Mario@dndisastri.test',
        'password' => 'password-lunga',
        'password_confirmation' => 'password-lunga',
        'played_before' => '0',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'Mario@dndisastri.test')->exists())->toBeTrue()
        ->and(User::count())->toBe(2);
});

it('per entrare l\'email va scritta com\'è registrata', function () {
    User::factory()->player()->create(['email' => 'Mario@dndisastri.test', 'password' => 'password-lunga']);

    $this->post(route('login'), ['email' => 'mario@dndisastri.test', 'password' => 'password-lunga'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login'), ['email' => 'Mario@dndisastri.test', 'password' => 'password-lunga']);
    $this->assertAuthenticated();
});
