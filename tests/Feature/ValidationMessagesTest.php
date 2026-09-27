<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

// Senza lang/it/validation.php Laravel stampava la chiave: chi si registrava
// con una password compromessa leggeva «validation.password.uncompromised».
it('non lascia trapelare nessuna chiave di traduzione', function (array $dati, array $regole) {
    $errori = Validator::make($dati, $regole)->errors()->all();

    expect($errori)->not->toBeEmpty();

    foreach ($errori as $errore) {
        expect($errore)->not->toStartWith('validation.');
    }
})->with([
    'obbligatorio' => [[], ['email' => ['required']]],
    'email' => [['email' => 'non-un-indirizzo'], ['email' => ['email']]],
    'troppo lungo' => [['name' => str_repeat('a', 300)], ['name' => ['max:255']]],
    'conferma' => [['password' => 'una', 'password_confirmation' => 'altra'], ['password' => ['confirmed']]],
    'numero' => [['gp' => 'tanto'], ['gp' => ['integer']]],
    'fuori intervallo' => [['gp' => -1], ['gp' => ['integer', 'min:0']]],
    'password compromessa' => [['password' => 'password'], ['password' => [Password::min(8)->uncompromised()]]],
]);

it('dice cosa fare quando la password è finita in una fuga di dati', function () {
    $errore = Validator::make(
        ['password' => 'password'],
        ['password' => [Password::min(8)->uncompromised()]],
    )->errors()->first('password');

    expect($errore)->toContain('scegline un\'altra');
});

// In italiano l'aggettivo concorderebbe col genere del campo, e «l'email è
// obbligatorio» si sente: le frasi sono scritte in forma neutra.
it('concorda con qualsiasi campo', function (string $campo, string $atteso) {
    $errore = Validator::make([], [$campo => ['required']])->errors()->first($campo);

    expect($errore)->toBe($atteso);
})->with([
    'femminile' => ['email', 'Serve l\'email.'],
    'maschile' => ['name', 'Serve il nome.'],
    'senza nome proprio' => ['pippo', 'Serve pippo.'],
]);
