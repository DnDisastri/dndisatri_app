@extends('layouts.guest')
@section('title', 'Nuova password')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field name="email" label="Email" type="email" :value="$email" autocomplete="email" required />
        <x-field name="password" label="Nuova password" type="password" autocomplete="new-password" required autofocus />
        <x-field name="password_confirmation" label="Ripeti la password" type="password"
                 autocomplete="new-password" required />

        <x-button size="lg">Imposta la password</x-button>
    </form>
@endsection
