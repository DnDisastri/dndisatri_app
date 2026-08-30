<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('accedi', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('accedi', [AuthenticatedSessionController::class, 'store']);

    Route::get('registrati', [RegisteredUserController::class, 'create'])->name('register');
    // Un freno anche qui: registrarsi è gratis, e senza limite è un rubinetto
    // di account aperto (il login ha già il suo, dentro LoginRequest).
    Route::post('registrati', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');

    Route::get('password-dimenticata', [PasswordResetLinkController::class, 'create'])->name('password.request');
    // Chiedere un link manda una email: senza freno è spam verso terzi.
    Route::post('password-dimenticata', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('reimposta-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reimposta-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.store');
});

Route::post('esci', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
