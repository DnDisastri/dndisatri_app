@extends('layouts.guest')
@section('title', 'Accesso')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" class="accent-active">
            Ricordami su questo dispositivo
        </label>

        <x-button size="lg">Entra <x-icona :is="\App\Enums\Icon::GoTo" class="h-5 w-5" /></x-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('password.request') }}" class="font-semibold text-fg hover:underline">
            Password dimenticata?
        </a>
    </p>

    <p class="mt-2 text-center text-sm text-muted">
        Non hai un account?
        <a href="{{ route('register') }}" class="font-semibold text-fg hover:underline">Registrati</a>
    </p>
@endsection
