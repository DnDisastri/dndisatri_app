@extends('layouts.app')
@section('title', 'Quest')

@section('content')

@php
    use App\Enums\Icon;
    use App\Enums\QuestDifficulty;
@endphp

<div class="mx-auto max-w-4xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="Icon::Quests" class="h-7 w-7" /> Quest
    </h2>
    <p class="mb-6 text-sm text-muted">Scegli la tua prossima avventura e preparati a partire.</p>

{{-- I filtri sono link e preservano l'altro parametro, così lo stato resta nell'URL e la pagina è condivisibile. --}}
    @if ($campaigns->count() > 1)
        <div class="mb-3 flex flex-wrap gap-2">
            <x-button size="sm" :variant="$campagna === null ? 'primary' : 'quiet'"
                      :href="route('quests.index', array_filter(['difficolta' => $difficolta?->value]))">
                Tutte le campagne
            </x-button>

            @foreach ($campaigns as $unaCampagna)
                <x-button size="sm" :variant="$campagna?->is($unaCampagna) ? 'primary' : 'quiet'"
                          :href="route('quests.index', array_filter([
                              'campagna' => $unaCampagna->slug,
                              'difficolta' => $difficolta?->value,
                          ]))">
                    {{ $unaCampagna->title }}
                </x-button>
            @endforeach
        </div>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        <x-button size="sm" :variant="$difficolta === null ? 'primary' : 'quiet'"
                  :href="route('quests.index', array_filter(['campagna' => $campagna?->slug]))">
            Tutte le difficoltà
        </x-button>

        @foreach (QuestDifficulty::cases() as $grado)
            <x-button size="sm" :variant="$difficolta === $grado ? 'primary' : 'quiet'"
                      :href="route('quests.index', array_filter([
                          'campagna' => $campagna?->slug,
                          'difficolta' => $grado->value,
                      ]))">
                {{ $grado->label() }}
            </x-button>
        @endforeach
    </div>

    <details class="mb-6 rounded-card border border-line bg-surface px-4 py-3">
        <summary class="cursor-pointer text-sm font-medium text-fg">
            Cosa vuol dire la difficoltà?
        </summary>
        <x-legenda-difficolta class="mt-3" />
    </details>

    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($quests as $quest)

            <x-quest-card :quest="$quest" :dim="$quest->isFull()" />
        @empty

            <x-empty size="lg" class="col-span-full">
                @if ($campagna || $difficolta)
                    Nessuna quest aperta con questi filtri.
                @else
                    Non c'è nessuna quest aperta in questo momento.
                @endif
            </x-empty>
        @endforelse
    </div>
</div>
@endsection
