@extends('layouts.app')
@section('title', 'Libro Mastro')

@php
// Mantiene season e campagna nell'URL, così la vista filtrata resta condivisibile.
    $conFiltri = fn (array $extra = []) => route('ledger.index', array_filter(array_merge([
        'season' => $season,
        'campagna' => $campaign?->slug,
    ], $extra)));
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Ledger" class="h-7 w-7" /> Libro Mastro
    </h2>
    <p class="mb-6 text-sm text-muted">
        La memoria del gruppo: le quest concluse, le serate giocate e chi
        non è tornato.
    </p>

    @if (count($seasons) > 1)
        <div class="mb-3 flex flex-wrap gap-2">
            <a href="{{ route('ledger.index') }}"
               @class([
                   'rounded-full px-4 py-1.5 text-sm font-semibold transition',
                   'bg-active text-on-active' => $season === null,
                   'bg-surface text-fg border border-line hover:border-active' => $season !== null,
               ])>Tutte le season</a>

            @foreach ($seasons as $numero)
{{-- Cambiare season azzera la campagna, che potrebbe non appartenere alla nuova season. --}}
                <a href="{{ route('ledger.index', ['season' => $numero]) }}"
                   @class([
                       'rounded-full px-4 py-1.5 text-sm font-semibold transition',
                       'bg-active text-on-active' => $season === $numero,
                       'bg-surface text-fg border border-line hover:border-active' => $season !== $numero,
                   ])>Season {{ $numero }}</a>
            @endforeach
        </div>
    @endif

    @if ($campaigns->count() > 1)
        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ $conFiltri(['campagna' => null]) }}"
               @class([
                   'rounded-full px-3 py-1 text-xs font-semibold transition',
                   'bg-primary text-on-primary' => $campaign === null,
                   'bg-surface text-fg border border-line hover:border-active' => $campaign !== null,
               ])>Tutte le campagne</a>

            @foreach ($campaigns as $c)
                <a href="{{ $conFiltri(['campagna' => $c->slug]) }}"
                   @class([
                       'rounded-full px-3 py-1 text-xs font-semibold transition',
                       'bg-primary text-on-primary' => $campaign?->is($c),
                       'bg-surface text-fg border border-line hover:border-active' => ! $campaign?->is($c),
                   ])>{{ $c->title }}</a>
            @endforeach
        </div>
    @endif

    <div class="space-y-8">

        <section>
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="\App\Enums\Icon::Sessions" class="h-5 w-5" /> Le serate giocate
            </h3>

            <div class="space-y-3">
                @forelse ($sessions as $session)
{{-- Il Libro Mastro mostra solo un'anteprima; `da=libro-mastro` conserva l'origine nella pagina della serata. --}}
                    <x-card :href="route('sessions.show', ['session' => $session, 'da' => 'libro-mastro'])" class="group">
                        <p class="text-xs uppercase tracking-wide text-muted">{{ $session->campaign?->title }}</p>
                        <p class="mt-1 font-semibold text-fg">{{ $session->displayTitle() }}</p>
                        <p class="text-sm text-muted">{{ $session->played_at->translatedFormat('j F Y') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ \Illuminate\Support\Str::limit($session->recap, 150) }}</p>

                        @if (\Illuminate\Support\Str::length($session->recap) > 150)
                            <span class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-muted transition group-hover:text-fg">
                                Leggi tutto <x-icona :is="\App\Enums\Icon::GoTo" class="h-4 w-4" />
                            </span>
                        @endif
                    </x-card>
                @empty
                    <x-empty>Nessuna serata con un resoconto, qui.</x-empty>
                @endforelse
            </div>
        </section>

        <section>
{{-- Il collegamento alle quest aperte conserva l'eventuale campagna selezionata. --}}
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                    <x-icona :is="\App\Enums\Icon::Quests" class="h-5 w-5" /> Le quest concluse
                </h3>
                <x-button size="sm" variant="quiet"
                          :href="route('quests.index', array_filter(['campagna' => $campaign?->slug]))">
                    Le quest aperte
                    <x-icona :is="\App\Enums\Icon::GoTo" class="h-4 w-4" />
                </x-button>
            </div>

            <p class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <x-badge tone="accent">Completata</x-badge> andata a buon fine
                </span>
                <span class="flex items-center gap-1.5">
                    <x-badge tone="neutral">Chiusa</x-badge> abbandonata
                </span>
            </p>

            <div class="space-y-2">
                @forelse ($quests as $quest)
                    <x-card padding="sm" :href="route('quests.show', $quest)"
                            class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <p class="font-semibold text-fg">{{ $quest->title }}</p>
                            <p class="text-xs text-muted">{{ $quest->campaign?->title }}</p>
                        </div>
                        <x-badge :tone="$quest->outcome() === \App\Enums\QuestOutcome::Completed ? 'accent' : 'neutral'">
                            {{ $quest->outcome()->label() }}
                        </x-badge>
                    </x-card>
                @empty
                    <x-empty>Nessuna quest conclusa, qui.</x-empty>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
