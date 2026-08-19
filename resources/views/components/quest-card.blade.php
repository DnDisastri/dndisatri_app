@props([
    'quest',
    'campaign' => true,
    'rewards' => false,
    'dim' => false,
])

{{--
    La card di un incarico, in elenco.

    Esisteva scritta a mano in tre posti — la Home, la pagina della campagna e
    l'elenco degli incarichi — con le stesse quattro righe e differenze
    piccole. È il motivo per cui «ne mancano 1» era sbagliato in tutti e tre
    insieme: una regola scritta tre volte si corregge una volta sola.

    L'anatomia è una e uguale ovunque; quello che cambia da un elenco all'altro
    sono tre cose, e sono proprietà:

    - `campaign` — il nome della campagna. Si spegne solo dentro la campagna
      stessa, dove ripeterlo su ogni card non direbbe niente.
    - `rewards` — le ricompense. Servono a chi sta scegliendo dentro un tavolo,
      non a chi scorre la bacheca di casa.
    - `dim` — la card spenta. **Lo decide chi chiama**, perché «spento» vuol
      dire due cose diverse: nella campagna è un incarico finito, nell'elenco è
      uno pieno in cui si può solo mettersi in fila.

    Come per `<x-button>`, sono assi che si sostituiscono e non classi:
    `$attributes->merge()` accoda, e non potrebbe toglierne una.
--}}
@php
    $mioPosto = $quest->seatOf(auth()->user());
@endphp

<x-card padding="sm" :href="route('quests.show', $quest)"
        {{ $attributes->merge(['class' => $dim ? 'opacity-60 hover:opacity-100' : '']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="font-semibold text-fg">{{ $quest->title }}</p>

        <div class="flex shrink-0 items-center gap-1.5">
            {{-- Il tipo, neutro: per ora tutte «Di campagna», ma il giorno che
                 arrivano boss run e farm è questa a distinguerle. --}}
            @if ($quest->type)
                <x-badge tone="neutral">{{ $quest->type->label() }}</x-badge>
            @endif

            @if ($quest->difficulty)
                <x-badge tone="accent">{{ $quest->difficulty->label() }}</x-badge>
            @endif
        </div>
    </div>

    {{-- Non è un collegamento: la card è già un collegamento, e uno dentro
         l'altro è HTML non valido. --}}
    @if ($campaign)
        <p class="mt-1 text-sm text-muted">{{ $quest->campaign?->title }}</p>
    @endif

    @if ($rewards && filled($quest->rewards))
        <p class="mt-1 text-sm text-muted">Ricompense: {{ $quest->rewards }}</p>
    @endif

    @if ($quest->isActive())
        {{-- Il plurale è scritto a mano: il pluralizzatore di Laravel ragiona
             in inglese e su «posto» sbaglia. --}}
        <p class="mt-3 text-sm text-muted">
            {{ $quest->participantCount() }} prenotati su {{ $quest->max_participants }} posti
        </p>

        {{-- La riga che dice se serve qualcuno, sotto e da sola: scorrendo un
             elenco è l'unica cosa che fa fermare, ed è «ne manca 1» che
             convince l'ultimo. --}}
        <p class="mt-1 text-sm">
            @if ($quest->missingToMinimum() > 0)
                {{-- Il plurale è scritto a mano anche qui: «manca 1 giocatore»
                     e «mancano 3 giocatori» cambiano tutte e due le parole, e
                     il pluralizzatore di Laravel ragiona in inglese. --}}
                <span class="font-semibold text-fg">
                    {{ $quest->missingToMinimum() === 1
                        ? 'Manca 1 giocatore'
                        : 'Mancano '.$quest->missingToMinimum().' giocatori' }}
                </span>
            @elseif ($quest->isNightConfirmed())
                <span class="font-semibold text-fg">La serata si fa</span>
            @elseif ($quest->isFull())
                <span class="text-muted">Posti esauriti — si entra in lista d'attesa</span>
            @else
                <span class="text-muted">Si può fare</span>
            @endif
        </p>
    @else
        <p class="mt-3 text-sm text-muted">{{ $quest->outcome()->label() }}</p>
    @endif

    {{-- Il proprio posto si vede da qui, o si aprirebbero tutti gli incarichi
         per ricordarsi dov'è che si è detto di sì.

         È una pillola e parla in seconda persona — `mine()` e non `label()` —
         perché «Prenotato» scritto lì dice che *qualcosa* è prenotato, non che
         sei stato tu. --}}
    @if ($quest->isActive() && $mioPosto?->isActive())
        <p class="mt-3">
            <x-badge tone="own">{{ $mioPosto->mine() }}</x-badge>
        </p>
    @endif
</x-card>
