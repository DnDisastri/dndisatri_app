@extends('layouts.guest')
@section('title', 'Registrazione')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="name" label="Nome utente" required autofocus />
        <x-field name="email" label="Email" type="email" autocomplete="email" required />
        <x-field name="password" label="Password" type="password" autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Ripeti la password" type="password"
                 autocomplete="new-password" required />

        <x-button size="lg">Crea account <x-icona :is="\App\Enums\Icon::GoTo" class="h-5 w-5" /></x-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Hai già un account?
        <a href="{{ route('login') }}" class="font-semibold text-fg hover:underline">Accedi</a>
    </p>
@endsection
