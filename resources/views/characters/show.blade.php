@extends('layouts.app')
@section('title', $character->name)

@section('content')
    <div class="mx-auto max-w-5xl space-y-4 px-4 py-6">

        @php $mio = auth()->id() === $character->user_id; @endphp

        <x-panel>
            <div class="flex flex-row justify-between items-center gap-1 mb-1">
                <h2 class="text-xl text-fg  ">{{ $character->name }}</h2>

                <x-menu-personaggio :character="$character" />
            </div>
            <div class="flex items-center gap-3 ">
                @if ($character->photoUrl())
                    <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
                        class="h-24 w-24 shrink-0 rounded-lg object-cover">
                @else
                    <span class="bg-page flex h-24 w-24 shrink-0 items-center justify-center rounded-lg  ">
                        <x-icona :is="\App\Enums\Icon::Characters" class="h-10 w-10 text-muted" />
                    </span>
                @endif
                <div class="flex flex-col items-start  w-full ">

                    <x-grado class="mt-1 self-end" :level="$character->level" />

                    <p class="text-sm ">
                        {{ $character->race }}
                    </p>
                    <p class="text-sm ">
                        <x-classi :character="$character" />
                    </p>
                    <p class="text-sm ">
                        Livello {{ $character->level }}
                    </p>
                </div>
            </div>

            @unless ($character->isAlive())
                <p class="mt-3 rounded-md border border-line bg-danger-soft px-3 py-2 text-sm text-on-danger-soft">
                    Caduto il {{ $character->died_at->format('d/m/Y') }}.
                </p>
            @endunless
            {{-- Le statistiche dinamiche restano nel componente Livewire così i dadi vita si aggiornano senza ricaricare la pagina. --}}
            @if ($completa)
                <div class=" pt-4">
                    <livewire:hit-point-tracker :character="$character" :key="'pf-' . $character->id" />
                </div>
            @endif

            @if ($character->isAlive())
            {{-- Evita di renderizzare i controlli senza autorizzazione; il componente Livewire verifica comunque i permessi lato server. --}}
                @can('grant', $character)
                    <div class="mt-4 border-t border-line pt-4">
                        <livewire:dm-tools :character="$character" :key="'dm-' . $character->id" />
                    </div>
                @endcan
            @endif
        </x-panel>

        {{-- Sezioni a swipe (app.js), senza reload; l'indirizzo segue la
             sezione, così un refresh riapre lì. --}}
        @if (count($sezioni) > 1)
            <nav class="flex overflow-x-auto rounded-full border border-line bg-surface text-sm"
                aria-label="Sezioni della scheda">
                @foreach ($sezioni as $voce)
                    <button type="button" data-sheet-tab data-url="{{ $voce->url($character) }}"
                        @if ($voce === $sezione) aria-current="page" @endif
                        class="flex-1 whitespace-nowrap rounded-full px-3 py-2 text-center font-medium text-muted
                               transition hover:text-fg aria-[current]:bg-primary aria-[current]:text-on-primary">
                        {{ $voce->label() }}
                    </button>
                @endforeach
            </nav>

            <div id="sheet-slider"
                class="flex snap-x snap-mandatory overflow-x-auto transition-[height] duration-200
                       [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($sezioni as $voce)
                    <div class="w-full shrink-0 snap-center snap-always self-start space-y-4">
                        @include('characters.sezioni.' . $voce->value)
                    </div>
                @endforeach
            </div>
        @else
            @include('characters.sezioni.' . $sezione->value)
        @endif

        <x-back dove="sotto" :href="$mio ? route('characters.index') : route('guild.index')">
            {{ $mio ? 'Torna ai miei eroi' : 'Torna alla Gilda' }}
        </x-back>
    </div>
@endsection
