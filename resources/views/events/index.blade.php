@extends('layouts.app')
@section('title', 'Eventi')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Events" class="h-7 w-7" /> Eventi
    </h2>
    <p class="mb-6 text-sm text-muted">
        Raduni, one-shot e serate speciali. Le serate di campagna stanno dentro
        la loro storia.
    </p>

    @if ($upcoming->isEmpty() && $past->isEmpty())
        <x-empty size="lg">Non c'è ancora nessun evento in programma.</x-empty>
    @endif
    @php
        $copertina = fn ($event) => $event->cover_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_path)
            : null;
    @endphp

    @if ($upcoming->isNotEmpty())
        <h3 class="mb-3 text-lg font-semibold text-fg">In arrivo</h3>
{{-- Usa `gap` perché lo spazio appartiene alla griglia; `space-y` sfalserebbe le card tra le righe. --}}
        <div class="mb-8 grid grid-cols-2 gap-1">
            @foreach ($upcoming as $event)
                <x-poster :href="route('events.show', $event)" :image="$copertina($event)"
                          :meta="'il '.$event->starts_at->format('d/m/y')"
                          :title="$event->title"
                          action="" variant="tile" />
            @endforeach
        </div>
    @endif

    @if ($past->isNotEmpty())
        <h3 class="mb-3 text-lg font-semibold text-fg">Già passati</h3>

        <div class="grid grid-cols-2 gap-1">
            @foreach ($past as $event)
                <x-poster :href="route('events.show', $event)" :image="$copertina($event)"
                          :meta="'il '.$event->starts_at->format('d/m/y')"
                          :title="$event->title"
                          action="" variant="tile" />
            @endforeach
        </div>
    @endif
</div>
@endsection
