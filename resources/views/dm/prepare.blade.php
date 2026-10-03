@extends('layouts.app')
@section('title', 'Prepara · '.$session->displayTitle())

@section('content')

<x-pagina class="space-y-6">

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
                {{ $session->numberLabel() }}@if (filled($session->title)): {{ $session->title }} @endif
            </span>
        </h1>
        <p class="mt-1 text-sm text-muted">{{ $session->played_at->translatedFormat('l j F Y, H:i') }}</p>
    </div>

    <div class="space-y-6 xl:grid xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] xl:items-start xl:gap-6 xl:space-y-0">
        <div class="xl:sticky xl:top-8">
            <livewire:session-prep :session="$session" />
        </div>

        <x-panel title="Combattimento">
            <livewire:combat-tracker :session="$session" />
        </x-panel>
    </div>
</x-pagina>
@endsection
