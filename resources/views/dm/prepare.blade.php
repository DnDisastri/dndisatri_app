@extends('layouts.app')
@section('title', 'Prepara · '.$session->displayTitle())

@section('content')

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

    <x-back dove="sopra" :href="route('sessions.show', ['session' => $session, 'da' => 'regia'])">
        Torna alla serata
    </x-back>

    <div>
        <p class="text-xs uppercase tracking-wide text-muted">
            <a href="{{ route('campaigns.show', $campagna) }}" class="transition hover:text-fg">{{ $campagna->title }}</a>
        </p>
        <h1 class="mt-1 text-2xl leading-tight text-fg">
            <span class="block">Prepara</span>
            <span class="block text-xl text-muted">
                {{ $session->numberLabel() }}@if (filled($session->title)) — {{ $session->title }} @endif
            </span>
        </h1>
        <p class="mt-1 text-sm text-muted">{{ $session->played_at->translatedFormat('l j F Y, H:i') }}</p>
    </div>

    <livewire:session-prep :session="$session" />

    <x-panel title="Combattimento">
        <livewire:combat-tracker :session="$session" />
    </x-panel>
</div>
@endsection
