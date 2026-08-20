@extends('layouts.app')
@section('title', 'Gilda')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-6">
    <h2 class="mb-1 flex items-center justify-center gap-2 text-center text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Guild" class="h-7 w-7" /> Gilda BlowUp
    </h2>
    <p class="mb-6 text-center text-sm italic text-muted">
        Tutti i membri della gilda
    </p>

    @if (auth()->user()->isDm())
        <form method="GET" action="{{ route('guild.index') }}" class="mx-auto mb-6 flex max-w-md items-center gap-2">
            <input type="search" name="cerca" value="{{ $cerca }}"
                   placeholder="Cerca per eroe o per giocatore"
                   class="flex-1 rounded-full border border-line bg-surface px-4 py-2 text-sm text-fg">
            <x-button>Cerca</x-button>
            @if ($cerca !== '')
                <a href="{{ route('guild.index') }}" class="whitespace-nowrap text-sm text-muted transition hover:text-fg">Azzera</a>
            @endif
        </form>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($characters as $character)
            <x-eroe :character="$character" :warn="in_array($character->user_id, $sottoRichiamo ?? [], true)" />
        @empty
            <p class="col-span-full text-center text-muted">
                @if (($cerca ?? '') !== '')
                    Nessun vivo corrisponde a «{{ $cerca }}».
                @else
                    Nessun personaggio vivo. La gilda è in cerca di avventurieri.
                @endif
            </p>
        @endforelse
    </div>

    @can('create', App\Models\Character::class)
        <p class="mt-8 text-center mb-2">
            <x-button size="lg" :href="route('characters.create')">Crea un personaggio</x-button>
        </p>
    @endcan
{{-- I caduti restano nella Gilda; `#caduti` mantiene il collegamento diretto usato dal menu e dai vecchi URL. --}}
    @if ($fallen->isNotEmpty())
        <section id="caduti" class="mt-12 scroll-mt-24 border-t border-line pt-8">
            <h3 class="mb-1 flex items-center justify-center gap-2 text-center text-xl text-fg">
                Hall of Fallen Heroes <x-icona :is="\App\Enums\Icon::Fallen" class="h-6 w-6" />
            </h3>
            <p class="mb-6 text-center text-sm italic text-muted">
                «In memoria di coloro che hanno dato tutto per la causa…»
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($fallen as $character)
                    <x-eroe :character="$character" :warn="in_array($character->user_id, $sottoRichiamo ?? [], true)" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
