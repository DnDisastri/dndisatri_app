@extends('layouts.app')
@section('title', $event->title)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-6">
    <x-back dove="sopra" :href="route('events.index')">Torna agli eventi</x-back>

    @if ($event->cover_path)
    {{-- La copertina è decorativa: il titolo dell'evento è già presente come testo subito sotto. --}}
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_path) }}"
             alt="" class="mb-4 w-full rounded-xl border border-line object-cover">
    @endif

    <h2 class="text-2xl text-fg">{{ $event->title }}</h2>

    <div class="mt-3 space-y-1 text-sm text-muted">
        <p class="flex items-center gap-2">
            <x-icona :is="\App\Enums\Icon::Events" class="h-4 w-4" />
            {{ $event->starts_at->translatedFormat('l j F Y, H:i') }}
            @if ($event->ends_at)
                — {{ $event->ends_at->isSameDay($event->starts_at)
                    ? $event->ends_at->format('H:i')
                    : $event->ends_at->translatedFormat('j F, H:i') }}
            @endif
        </p>

        @if (filled($event->location))
            <p>{{ $event->location }}</p>
        @endif
    </div>

    @if (filled($event->description))
        <p class="mt-5 whitespace-pre-line text-fg">{{ $event->description }}</p>
    @endif

    <x-reactions :for="$event" class="mt-6 border-t border-line pt-4" />
</div>
@endsection
