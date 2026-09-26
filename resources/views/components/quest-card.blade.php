@props([
    'quest',
    'campaign' => true,
    'rewards' => false,
    'dim' => false,
])

{{--
    La card di un incarico, una per tutti gli elenchi (Home, campagna,
    incarichi). Tre assi come proprietà:

    - `campaign` — il nome della campagna (spento dentro la campagna stessa);
    - `rewards` — le ricompense (servono a chi sceglie a un tavolo);
    - `dim` — la card spenta, deciso da chi chiama («spento» = finito nella
      campagna, pieno nell'elenco).
--}}
@php
    $mioPosto = $quest->seatOf(auth()->user());
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
        {{-- Plurale a mano: il pluralizzatore di Laravel ragiona in inglese. --}}
        <p class="mt-3 text-sm text-muted">
            {{ $quest->participantCount() }} prenotati su {{ $quest->max_participants }} posti
        </p>

        {{-- Se serve qualcuno, sotto e da solo: è la riga che fa fermare scorrendo. --}}
        <p class="mt-1 text-sm">
            @if ($quest->missingToMinimum() > 0)
                {{-- Plurale a mano anche qui (in inglese sbaglierebbe). --}}
                <span class="font-semibold text-fg">
                    {{ $quest->missingToMinimum() === 1
                        ? 'Manca 1 giocatore'
                        : 'Mancano '.$quest->missingToMinimum().' giocatori' }}
                </span>
            @elseif ($quest->isNightConfirmed())
                <span class="font-semibold text-fg">La serata si fa</span>
            @elseif ($quest->isFull())
                <span class="text-muted">Posti esauriti, si entra in lista d'attesa</span>
            @else
                <span class="text-muted">Si può fare</span>
            @endif
        </p>
    @else
        <p class="mt-3 text-sm text-muted">{{ $quest->outcome()->label() }}</p>
    @endif

    {{-- Il proprio posto, in seconda persona (`mine()`, non `label()`):
         «Prenotato» da solo direbbe che qualcosa è prenotato, non che sei tu. --}}
    @if ($quest->isActive() && $mioPosto?->isActive())
        <p class="mt-3">
            <x-badge tone="own">{{ $mioPosto->mine() }}</x-badge>
        </p>
    @endif
</x-card>
