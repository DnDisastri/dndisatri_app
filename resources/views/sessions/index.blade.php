@extends('layouts.app')
@section('title', 'Calendario')

@section('content')

@php
    use App\Enums\Icon;

    $oggi = today();
    $primo = $mese->copy()->startOfMonth();

    // `dayOfWeekIso` parte da 1 per lunedì; il resto sono celle vuote prima del giorno 1.
    $vuotePrima = $primo->dayOfWeekIso - 1;
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

    <div>
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="Icon::Sessions" class="h-7 w-7" /> Calendario
        </h2>
        <p class="mt-1 text-sm text-muted">Quando si gioca, tavolo per tavolo.</p>
    </div>

    <x-panel>
{{-- Il mese resta nell'URL, così la vista può essere condivisa e riaperta nello stesso stato. --}}
        <div class="flex items-center justify-between gap-3">
            <x-button variant="quiet" size="sm"
                      :href="route('sessions.index', ['mese' => $mese->copy()->subMonth()->format('Y-m')])"
                      aria-label="Il mese prima">‹</x-button>

            <p class="text-lg font-semibold capitalize text-fg">{{ $mese->translatedFormat('F Y') }}</p>

            <x-button variant="quiet" size="sm"
                      :href="route('sessions.index', ['mese' => $mese->copy()->addMonth()->format('Y-m')])"
                      aria-label="Il mese dopo">›</x-button>
        </div>

        <div class="mt-4 grid grid-cols-7 gap-1 text-center">
            @foreach (['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $giorno)
                <span class="pb-1 text-xs uppercase tracking-wide text-muted">{{ $giorno }}</span>
            @endforeach

{{-- Le celle vuote servono solo all'allineamento del calendario e vengono nascoste alle tecnologie assistive. --}}
            @for ($i = 0; $i < $vuotePrima; $i++)
                <span aria-hidden="true"></span>
            @endfor

            @foreach (range(1, $mese->daysInMonth) as $numero)
                @php
                    $giorno = $primo->copy()->setDay($numero);
                    $quelGiorno = $perGiorno[$giorno->toDateString()] ?? collect();
                @endphp

                @if ($quelGiorno->isNotEmpty())
                    <a href="#g-{{ $giorno->toDateString() }}"
                       title="{{ $quelGiorno->count() }} {{ $quelGiorno->count() === 1 ? 'serata' : 'serate' }}"
                       @class([
                           'flex aspect-square items-center justify-center rounded-lg text-sm font-bold transition',
                           'bg-active text-on-active hover:opacity-90',
                           'ring-2 ring-fg' => $giorno->isSameDay($oggi),
                       ])>{{ $numero }}</a>
                @else
                    <span @class([
                        'flex aspect-square items-center justify-center rounded-lg text-sm text-muted',
                        'ring-2 ring-fg font-bold text-fg' => $giorno->isSameDay($oggi),
                    ])>{{ $numero }}</span>
                @endif
            @endforeach
        </div>
    </x-panel>

    <section>
        <h3 class="mb-3 font-display text-lg font-normal capitalize text-fg">
            Le serate di {{ $mese->translatedFormat('F') }}
        </h3>

{{-- Solo la prima serata del giorno riceve l'ancora, così ogni `id` resta univoco. --}}
        @php $ancorati = []; @endphp

        <div class="space-y-2">
            @forelse ($sessions as $session)
                @php
                    $data = $session->played_at->toDateString();
                    $ancora = in_array($data, $ancorati, true) ? null : $ancorati[] = $data;
                @endphp

                <x-card padding="sm" :href="route('sessions.show', ['session' => $session, 'da' => 'serate'])"
                        :id="$ancora ? 'g-'.$ancora : null"
                        class="flex items-baseline justify-between gap-3 scroll-mt-20">
                    <span>
                        <span class="block font-semibold text-fg">{{ $session->campaign?->title }}</span>
                        <span class="block text-sm text-muted">{{ $session->displayTitle() }}</span>

                        @if ($session->isUpcoming())
                            <x-badge tone="accent" class="mt-1">Da giocare</x-badge>
                        @endif
                    </span>

                    <span class="shrink-0 text-right text-sm text-muted">
                        {{ $session->played_at->translatedFormat('D j') }}<br>
                        {{ $session->played_at->format('H:i') }}
                    </span>
                </x-card>
            @empty
                <x-empty>Nessuna serata in questo mese.</x-empty>
            @endforelse
        </div>
    </section>

{{-- Gli eventi mostrano sempre i prossimi appuntamenti e non seguono il mese selezionato nel calendario. --}}
    @if ($events->isNotEmpty())
        <section>
            <div class="mb-3">
                <h3 class="font-display flex items-center gap-2 text-lg font-normal text-fg">
                    <x-icona :is="Icon::Events" class="h-5 w-5" /> La bacheca del bardo
                </h3>
                <p class="mt-1 text-sm text-muted">Ecco i prossimi eventi in programma</p>
            </div>

            <div class=" flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2
                        [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($events as $event)
                    <x-poster
                        :href="route('events.show', $event)"
                        :image="$event->cover_path
                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_path)
                            : null"
                        label="Nuovo evento"
                        :meta="'il '.$event->starts_at->format('d/m/y')"
                        :title="$event->title"
                        class="w-72 shrink-0 snap-start" />
                @endforeach
            </div>

            <x-button variant="secondary" size="lg" full class="mt-3" :href="route('events.index')">
                Vedi tutti gli eventi
            </x-button>
        </section>
    @endif
</div>
@endsection
