@extends('layouts.app')
@section('title', $character->name)

@php
// `created_at` rappresenta da quando il personaggio esiste nell'app, non la sua nascita narrativa.
// Calcola la durata solo con date coerenti, evitando risultati assurdi su dati importati.
    $dal = $character->created_at;
    $al = $character->died_at;
    $durata = $dal && $al->greaterThan($dal) ? $al->longAbsoluteDiffForHumans($dal) : null;
@endphp

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">

    <x-back dove="sopra" :href="route('guild.index').'#caduti'" class="-mb-1">
        Torna alla Gilda
    </x-back>

    <x-panel class="text-center">
        @if ($character->photoUrl())

            <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
                 class="mx-auto h-32 w-32 rounded-lg object-cover grayscale">
        @else
            <span class="mx-auto flex h-32 w-32 items-center justify-center rounded-lg bg-page">
                <x-icona :is="\App\Enums\Icon::Fallen" class="h-12 w-12 text-muted" />
            </span>
        @endif

        <h2 class="mt-3 flex items-center justify-center gap-2 text-2xl text-fg">
            {{ $character->name }}
            <x-icona :is="\App\Enums\Icon::Fallen" class="h-6 w-6 shrink-0 text-on-danger-soft" />
        </h2>

        <p class="mt-1 text-sm text-muted">
            {{ $character->race }} · <x-classi :character="$character" />
            · livello {{ $character->level }}
        </p>
        <p class="mt-1 text-sm text-muted">Giocato da {{ $character->user->name }}</p>
        <x-grado class="mt-2" :level="$character->level" />
        <p class="mt-2 text-sm text-on-danger-soft">
            Caduto il {{ $character->died_at->translatedFormat('j F Y') }}
        </p>

        @if ($durata)
            <p class="text-xs text-muted">
                Ha giocato dal {{ $dal->translatedFormat('j F Y') }}, {{ $durata }}.
            </p>
        @endif
    </x-panel>

    <x-panel title="Com'è andata">
        @if (filled($character->death_story))
{{-- Escape prima di `nl2br()` per conservare gli a capo senza renderizzare HTML arbitrario. --}}
            <p class="text-sm text-fg">{!! nl2br(e($character->death_story)) !!}</p>
        @else
            <x-empty>Di come sia andata non è rimasto scritto niente.</x-empty>
        @endif

{{-- La serata della morte è facoltativa: una morte può essere registrata anche fuori da una sessione. --}}
        @if ($character->diedInSession)
            <p class="mt-4 border-t border-line pt-3 text-sm">
                <a href="{{ route('sessions.show', $character->diedInSession) }}"
                   class="inline-flex items-center gap-1.5 text-fg hover:underline">
                    {{ $character->diedInSession->displayTitle() }}
                    <x-icona :is="\App\Enums\Icon::GoTo" class="h-4 w-4 shrink-0" />
                </a>
                @if ($character->diedInSession->campaign)
                    <span class="text-muted">· {{ $character->diedInSession->campaign->title }}</span>
                @endif
            </p>
        @endif
    </x-panel>
    
    <x-button variant="secondary" size="lg" full
              :href="route('characters.show', $character)">
        La sua scheda
    </x-button>
</div>
@endsection
