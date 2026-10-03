@extends('layouts.app')
@section('title', 'I miei eroi')

@section('content')
    <x-pagina class="space-y-6">
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="\App\Enums\Icon::Characters" class="h-7 w-7" /> I miei eroi
        </h2>

        <div class="space-y-6 lg:grid lg:grid-cols-3 lg:items-start lg:gap-6 lg:space-y-0">
        <div class="space-y-6 lg:col-span-2">
        @if ($characters->isEmpty())
            <x-panel>
                <p class="text-lg font-semibold text-fg">Non hai ancora un personaggio</p>
                <p class="mt-1 text-sm text-muted">
                    La creazione guidata ti porta passo per passo: classe, specie,
                    caratteristiche, background, abilità, equipaggiamento e incantesimi.

                </p>
                <x-button size="lg" class="mt-4 w-full" :href="route('characters.create')">Crea il tuo personaggio</x-button>

                <p class="mt-3 text-center">
                    <a href="{{ route('builds.index') }}" class="text-sm text-muted hover:underline">
                        …o parti da una build consigliata
                    </a>
                </p>
            </x-panel>
        @else
            {{-- `auto-fit`: un eroe solo occupa tutta la colonna invece di lasciarne mezza vuota. --}}
            <div class="grid gap-6 md:grid-cols-[repeat(auto-fit,minmax(18rem,1fr))] md:gap-4">
            @foreach ($characters as $character)
                <x-panel class="flex flex-col">
                    <div class="flex flex-row justify-between items-center gap-1 mb-1">
                        <p class="text-lg font-normal font-display text-fg">{{ $character->name }}</p>
                        <x-menu-personaggio :character="$character" />
                    </div>
                    {{-- `flex-1`: nella griglia i pulsanti restano allineati anche se le card hanno altezze diverse. --}}
                    <div class="flex flex-1 items-center justify-center gap-3">
                        @if ($character->photoUrl())
                            <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
                                class="h-20 w-20 shrink-0 rounded-lg object-cover md:h-28 md:w-28">
                        @else
                            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-page md:h-28 md:w-28">
                                <x-icona :is="\App\Enums\Icon::Characters" class="h-8 w-8 text-muted" />
                            </span>
                        @endif

                        <div class="min-w-0 flex flex-col w-full ">
                            <x-grado class="self-end" :level="$character->level" />

                            <p class="text-sm ">
                                {{ $character->speciesLabel() }} 
                            </p>
                            <p class="text-sm ">
                                <x-classi :character="$character" />
                            </p>
                            <p class="text-sm ">
                                Livello {{ $character->level }}
                            </p>
                            <p class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted">
                                <span class="flex items-center gap-1" title="Punti Ferita">
                                    <x-icona :is="\App\Enums\Icon::HitPoints" class="h-4 w-4" />
                                    {{ $character->hp_current }}/{{ $character->effectiveHpMax() }}
                                </span>
                                <span class="flex items-center gap-1" title="Oro">
                                    <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" /> {{ $character->gp }}
                                </span>
                            </p>

                            @unless ($character->isAlive())
                                <x-badge tone="danger" class="mt-2">
                                    Caduto il {{ $character->died_at->translatedFormat('j F Y') }}
                                </x-badge>
                            @endunless
                        </div>

                    </div>
                    <x-button variant="secondary" size="lg" full class="mt-4" :href="route('characters.show', $character)">
                        Vai alla scheda
                    </x-button>
                </x-panel>
            @endforeach
            </div>
        @endif
        </div>

        <x-panel>
            <div class="flex items-baseline justify-between gap-2">
                <h3 class="text-lg font-semibold text-fg">Le mie richieste</h3>
                <a href="{{ route('proposals.index') }}" class="text-sm text-muted hover:underline">Tutte</a>
            </div>

            <div class="mt-3 space-y-2">
                @forelse ($changes as $change)
                    <x-inset padding="sm" class="flex flex-wrap items-baseline justify-between gap-2">
                        <span class="text-sm text-fg">
                            {{ $change->type->label() }}
                            <span class="text-muted">· {{ $change->character?->name }}</span>
                        </span>

                        @php
                            $colore = match ($change->status) {
                                \App\Enums\PendingChangeStatus::Pending => 'text-on-accent-soft',
                                \App\Enums\PendingChangeStatus::Approved => 'text-primary',
                                \App\Enums\PendingChangeStatus::Rejected => 'text-on-danger-soft',
                            };
                        @endphp
                        <span class="text-sm font-semibold {{ $colore }}">{{ $change->status->label() }}</span>
                    </x-inset>
                @empty
                    <p class="text-sm text-muted">Non hai ancora effettuato richieste.</p>
                @endforelse
            </div>
        </x-panel>
        </div>

        @if ($characters->isNotEmpty())
            <div class="space-y-2 text-center">
                @can('create', App\Models\Character::class)
                    <p>
                        <a href="{{ route('characters.create') }}" class="text-sm text-muted hover:underline">Crea un altro personaggio</a>
                    </p>
                @endcan

                <p>
                    <a href="{{ route('builds.index') }}" class="text-sm text-muted hover:underline">Sfoglia le build consigliate</a>
                </p>
            </div>
        @endif

        <x-back :href="route('home')">Torna alla Home</x-back>
    </x-pagina>
@endsection
