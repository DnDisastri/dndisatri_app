@extends('layouts.app')
@section('title', 'Home')

@section('content')
<div class="mx-auto max-w-3xl space-y-10 px-4 py-6">

    <div>
        <h2 class="text-2xl text-fg">Bentornato, {{ auth()->user()->name }}</h2>
    </div>
{{-- Il carosello usa CSS scroll-snap e resta navigabile anche senza JavaScript. --}}
    @if ($events->isNotEmpty())
        <section>
            <div class="mb-3">
                <h3 class="font-display flex items-center gap-2 text-lg font-normal text-fg">
                    <x-icona :is="\App\Enums\Icon::Events" class="h-5 w-5" /> La bacheca del bardo
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

    <section>
        <div class="mb-3">
            <h3 class="font-display flex items-center gap-2 text-lg font-normal text-fg">
                <x-icona :is="\App\Enums\Icon::Campaigns" class="h-5 w-5" /> Le campagne
            </h3>
            <p class="mt-1 text-sm text-muted">Le storie aperte in questo momento</p>
        </div>

        <div class="grid grid-cols-2 gap-1">
            @forelse ($campaigns as $campaign)

                <x-poster variant="tile"
                          :href="route('campaigns.show', $campaign)"
                          :image="$campaign->coverUrl()"
                          :title="$campaign->title"
                          action="" />
            @empty
                <x-empty class="col-span-full">Nessuna campagna aperta in questo momento.</x-empty>
            @endforelse
        </div>

        <x-button variant="secondary" size="lg" full class="mt-3" :href="route('campaigns.index')">
            Vedi tutte le campagne
        </x-button>
    </section>

    <section>
        <h3 class="font-display mb-3 flex items-center gap-2 text-lg font-normal text-fg">
            <x-icona :is="\App\Enums\Icon::Sessions" class="h-5 w-5" /> I prossimi tavoli
        </h3>

        <div class="space-y-2">
            @forelse ($sessions as $session)

                <x-card padding="sm" :href="route('sessions.show', $session)"
                        class="flex items-baseline justify-between gap-3">
                    <span>
                        <span class="block font-semibold text-fg">{{ $session->campaign?->title }}</span>
                        <span class="block text-sm text-muted">{{ $session->displayTitle() }}</span>
                    </span>
                    <span class="shrink-0 text-right text-sm text-muted">
                        {{ $session->played_at->translatedFormat('j M') }}<br>
                        {{ $session->played_at->format('H:i') }}
                    </span>
                </x-card>
            @empty
                <x-empty>Nessun tavolo in programma.</x-empty>
            @endforelse
        </div>

        <x-button variant="secondary" size="lg" full class="mt-3" :href="route('sessions.index')">
            Vedi il calendario
        </x-button>
    </section>

    <section>
        <h3 class="font-display mb-3 flex items-center gap-2 text-lg font-normal text-fg">
            <x-icona :is="\App\Enums\Icon::Quests" class="h-5 w-5" /> Le quest
        </h3>

        <div class="space-y-2">
            @forelse ($quests as $quest)

                <x-quest-card :quest="$quest" />
            @empty
                <x-empty>Nessuna quest aperta in questo momento.</x-empty>
            @endforelse
        </div>

        <x-button variant="secondary" size="lg" full class="mt-3" :href="route('quests.index')">
            Vedi tutte le quest
        </x-button>
    </section>

    @if ($posts->isNotEmpty())
        <section>
            <h3 class="font-display font-normal mb-3 flex items-center gap-2 text-lg  text-fg">
                <x-icona :is="\App\Enums\Icon::News" class="h-5 w-5" /> News & novità
            </h3>

            <div class="space-y-3">
                @foreach ($posts as $post)
                    <x-card padding="sm" :href="route('news.show', $post)">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="font-semibold text-fg">{{ $post->title }}</p>

                            @if ($post->is_pinned)
                                <x-icona :is="\App\Enums\Icon::Featured" class="h-4 w-4 shrink-0 text-on-accent-soft"
                                         title="In evidenza" />
                            @endif
                        </div>

                        @if (filled($post->excerpt))
                            <p class="mt-1 text-sm text-muted">{{ $post->excerpt }}</p>
                        @endif

                        <p class="mt-1 text-xs text-muted">
                            {{ $post->published_at->translatedFormat('j F Y') }}
                        </p>
                    </x-card>
                @endforeach
            </div>

            <x-button variant="secondary" size="lg" full class="mt-3" :href="route('news.index')">
                Vedi tutte le news
            </x-button>
        </section>
    @endif
</div>
@endsection
