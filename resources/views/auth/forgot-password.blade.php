@extends('layouts.guest')
@section('title', 'Password dimenticata')

@section('content')
    <p class="mb-4 text-sm text-muted">
        Inserisci il tuo indirizzo: ti mandiamo un link per impostare una password nuova.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />

        <x-button size="lg">Mandami il link</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('login') }}" class="font-semibold text-fg hover:underline">Torna all'accesso</a>
    </p>
@endsection
