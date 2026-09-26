@extends('layouts.app')
@section('title', 'News')

@section('content')

@php use App\Enums\Icon; @endphp

<div class="mx-auto max-w-2xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="Icon::News" class="h-7 w-7" /> News
    </h2>
    <p class="mb-6 text-sm text-muted">Gli annunci della gilda, dal più recente.</p>

    <div class="space-y-4">
        @forelse ($posts as $post)
{{-- `flush` permette alla copertina di arrivare fino ai bordi della card. --}}
            <x-card flush padding="none" :href="route('news.show', $post)">
                @if ($post->cover_path)
                {{-- La copertina è decorativa: il titolo della news è già presente come testo nella card. --}}
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover_path) }}"
                         alt="" class="aspect-[21/9] w-full object-cover">
                @endif

                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold text-fg">{{ $post->title }}</p>

{{-- Segnala perché una news può comparire fuori dal normale ordine cronologico. --}}
                        @if ($post->is_pinned)
                            <x-badge tone="accent" class="shrink-0">In evidenza</x-badge>
                        @endif
                    </div>

                    @if (filled($post->excerpt))
                        <p class="mt-2 text-sm text-muted">{{ $post->excerpt }}</p>
                    @endif

                    <p class="mt-3 text-xs text-muted">
                        {{ $post->published_at->translatedFormat('j F Y') }}
                        @if ($post->author)
                            · {{ $post->author->name }}
                        @endif
                    </p>
                </div>
            </x-card>
        @empty
            <x-empty size="lg">Non c'è ancora nessuna news.</x-empty>
        @endforelse
    </div>
</div>
@endsection
