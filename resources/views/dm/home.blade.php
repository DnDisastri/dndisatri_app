@extends('layouts.app')
@section('title', 'Regia')

@section('content')

@php
    use App\Enums\Icon;

    $quando = $serata?->played_at;

// Mostra la serata di oggi, altrimenti la prossima; se non ce ne sono future usa l'ultima giocata.
    $statoSerata = match (true) {
        $serata === null => null,
        $quando->isToday() => 'oggi',
        $quando->isFuture() => 'prossima',
        default => 'ultima',
    };
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide text-muted">Regia</p>
            <h1 class="truncate text-2xl text-fg">
                {{ $corrente?->title ?? 'Nessuna campagna' }}
            </h1>
        </div>

        @if ($mie->isNotEmpty() || $altre->isNotEmpty())
            <details class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-line
                                bg-surface px-3 py-2 text-sm font-semibold text-fg transition hover:border-active
                                [&::-webkit-details-marker]:hidden">
                    Cambia
                    <x-icona :is="Icon::Expand" class="h-4 w-4" />
                </summary>

                <nav class="absolute right-0 z-10 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-surface shadow-lg shadow-black/10">
                    @if ($mie->isNotEmpty())
                        <p class="border-b border-line px-4 py-2 text-xs uppercase tracking-wide text-muted">Le mie</p>
                        @foreach ($mie as $c)
                            <a href="{{ route('dm.home', ['campagna' => $c->slug]) }}"
                               @class([
                                   'block px-4 py-2.5 text-sm hover:bg-page',
                                   'font-semibold text-active' => $corrente && $c->is($corrente),
                                   'text-fg' => ! ($corrente && $c->is($corrente)),
                               ])>{{ $c->title }}</a>
                        @endforeach
                    @endif

                    @if ($altre->isNotEmpty())
                        <p class="border-b border-t border-line px-4 py-2 text-xs uppercase tracking-wide text-muted">
                            Degli altri · emergenza
                        </p>
                        @foreach ($altre as $c)
                            <a href="{{ route('dm.home', ['campagna' => $c->slug]) }}"
                               @class([
                                   'block px-4 py-2.5 text-sm hover:bg-page',
                                   'font-semibold text-active' => $corrente && $c->is($corrente),
                                   'text-fg' => ! ($corrente && $c->is($corrente)),
                               ])>
                                {{ $c->title }}
                                <span class="block text-xs text-muted">conduce {{ $c->dm?->name ?? 'Vuoto' }}</span>
                            </a>
                        @endforeach
                    @endif
                </nav>
            </details>
        @endif
    </div>

    @if ($sostituto)
        <x-note>
            Stai coprendo <strong>{{ $corrente->dm?->name ?? 'un collega' }}</strong> su
            «{{ $corrente->title }}». Puoi condurre, ma la campagna resta sua.
        </x-note>
    @endif

    @if ($corrente === null)
        <x-empty>
            Non ci sono campagne attive da condurre. Quando ne apri una dal Pannello,
            comparirà qui.
        </x-empty>
    @else
        <section class="space-y-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xs uppercase tracking-wide text-muted">
                    {{ $statoSerata === 'ultima' ? 'Ultima serata' : 'Stasera' }}
                </h2>
                <a href="{{ route('sessions.index') }}" class="text-xs font-semibold text-active">Tutte le serate ›</a>
            </div>

            @if ($serata === null)
                <x-empty>Nessuna serata in programma per questa campagna.</x-empty>
            @else
                <x-panel class="ring-1 ring-active">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($statoSerata === 'oggi')
                                <x-badge tone="own">In corso · oggi</x-badge>
                            @elseif ($statoSerata === 'prossima')
                                <x-badge tone="accent">Prossima</x-badge>
                            @else
                                <x-badge tone="neutral">Giocata</x-badge>
                            @endif

                            <p class="mt-2 font-display text-lg leading-tight text-fg">
                                {{ $serata->numberLabel() }}
                                @if (filled($serata->title))
                                    <span class="block text-base text-muted">{{ $serata->title }}</span>
                                @endif
                            </p>

                            <p class="mt-1 text-sm text-muted">
                                {{ $serata->played_at->translatedFormat('l j F, H:i') }}
                                · {{ $tavolo->count() }} {{ $tavolo->count() === 1 ? 'eroe' : 'eroi' }} al tavolo
                            </p>
                        </div>
                    </div>
{{-- `da=regia` conserva l'origine, così dalla serata il DM può tornare alla Regia. --}}
                    <a href="{{ route('sessions.show', ['session' => $serata, 'da' => 'regia']) }}"
                       class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-active px-4 py-3
                              text-sm font-bold text-on-active transition hover:opacity-90">
                        Conduci la serata
                        <x-icona :is="Icon::GoTo" class="h-4 w-4" />
                    </a>
                </x-panel>
            @endif
        </section>

        <section class="space-y-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xs uppercase tracking-wide text-muted">Il tavolo</h2>
                <a href="{{ route('guild.index') }}" class="text-xs font-semibold text-active">Gilda ›</a>
            </div>

            @include('dm.partials.tavolo', ['tavolo' => $tavolo])
        </section>
    @endif
</div>
@endsection
