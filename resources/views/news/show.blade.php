@extends('layouts.app')
@section('title', $post->title)

@section('content')

<div class="mx-auto max-w-2xl px-4 py-6">

{{-- Gli admin possono aprire anche bozze e pubblicazioni programmate; la nota chiarisce che non sono ancora visibili agli utenti. --}}
    @unless ($post->isPublished())
        <x-note class="mb-4">
            @if ($post->isDraft())
                Questa news è una <strong>bozza</strong>: la vedi perché sei un
                admin, e nessun altro la vede.
            @else
                Questa news esce il
                <strong>{{ $post->published_at->translatedFormat('j F Y') }}</strong>:
                fino ad allora la vedi solo tu.
            @endif
        </x-note>
    @endunless

    @if ($post->cover_path)
        <div class="relative mb-4">
            {{-- La copertina è decorativa: il titolo della news è già presente come testo. --}}
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover_path) }}"
                 alt="" class="w-full rounded-card border border-line object-cover">

            @if ($post->is_pinned)
                <x-badge tone="accent" class="absolute right-3 top-3">
{{-- Mantiene il layout flex all'interno del badge senza modificare il display del componente. --}}
                    <span class="inline-flex items-center gap-1">
                        <x-icona :is="\App\Enums\Icon::Featured" class="h-4 w-4" />
                        In evidenza
                    </span>
                </x-badge>
            @endif
        </div>
    @endif

    <div class="flex items-start justify-between gap-3">
        <h2 class="text-2xl text-fg">{{ $post->title }}</h2>

        @if ($post->is_pinned && ! $post->cover_path)
            <x-badge tone="accent" class="mt-1 shrink-0">
                <span class="inline-flex items-center gap-1">
                    <x-icona :is="\App\Enums\Icon::Featured" class="h-4 w-4" />
                    In evidenza
                </span>
            </x-badge>
        @endif
    </div>

    <p class="mt-2 text-sm text-muted">
        @if ($post->published_at)
            {{ $post->published_at->translatedFormat('j F Y') }}
        @endif
        @if ($post->author)
            · {{ $post->author->name }}
        @endif
    </p>

 {{-- Blade esegue l'escape del testo; `whitespace-pre-line` conserva gli a capo senza renderizzare HTML. --}}
    @if (filled($post->excerpt))
        <p class="mt-5 text-lg text-muted">{{ $post->excerpt }}</p>
    @endif

    @if (filled($post->body))
        <p class="mt-4 whitespace-pre-line text-fg">{{ $post->body }}</p>
    @endif
    
{{-- Le reazioni sono disponibili solo quando il post è effettivamente pubblicato. --}}
    @if ($post->acceptsReactions())
        <x-reactions :for="$post" class="mt-6 border-t border-line pt-4" />
    @endif

    <x-back :href="route('news.index')">Torna alle news</x-back>
</div>
@endsection
