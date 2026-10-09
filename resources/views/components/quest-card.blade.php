@props([
    'quest',
    'campaign' => true,
    'rewards' => false,
    'dim' => false,
])

{{-- La card di una quest in Home, campagna ed elenco. `campaign`: mostra il nome
     della campagna; `rewards`: le ricompense; `dim`: la card spenta. --}}
@php
    $miInteressa = $quest->isActive() && $quest->isInterested(auth()->user());
    $interessati = $quest->interested_count ?? $quest->interested()->count();
@endphp

<x-card padding="sm" :href="route('quests.show', $quest)"
        {{ $attributes->merge(['class' => $dim ? 'opacity-60 hover:opacity-100' : '']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="font-semibold text-fg">{{ $quest->title }}</p>

        <div class="flex shrink-0 items-center gap-1.5">
            {{-- Il tipo, neutro: per ora tutte «Di campagna», distingue quando arriveranno boss run e farm. --}}
            @if ($quest->type)
                <x-badge tone="neutral">{{ $quest->type->label() }}</x-badge>
            @endif

            @if ($quest->difficulty)
                <x-badge tone="accent">{{ $quest->difficulty->label() }}</x-badge>
            @endif
        </div>
    </div>

    {{-- Non è un link: la card lo è già, e uno dentro l'altro è HTML non valido. --}}
    @if ($campaign)
        <p class="mt-1 text-sm text-muted">{{ $quest->campaign?->title }}</p>
    @endif

    @if ($rewards && filled($quest->rewards))
        <p class="mt-1 text-sm text-muted">Ricompense: {{ $quest->rewards }}</p>
    @endif

    @if ($quest->isActive())
        <p class="mt-3 text-sm">
            @if ($quest->isScheduled())
                <span class="font-semibold text-fg">Si gioca {{ $quest->session->played_at->translatedFormat('l j F') }}</span>
            @else
                <span class="text-muted">Non ancora in una sessione</span>
            @endif
        </p>

        {{-- Plurale a mano: il pluralizzatore di Laravel ragiona in inglese. --}}
        <p class="mt-1 text-sm text-muted">
            {{ $interessati === 1 ? 'Interessa a 1 giocatore' : 'Interessa a '.$interessati.' giocatori' }}
        </p>
    @else
        <p class="mt-3 text-sm text-muted">{{ $quest->outcome()->label() }}</p>
    @endif

    @if ($miInteressa)
        <p class="mt-3">
            <x-badge tone="own">Ti interessa</x-badge>
        </p>
    @endif
</x-card>
