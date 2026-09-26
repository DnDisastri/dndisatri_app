@extends('layouts.app')
@section('title', $campaign->title)

@section('content')
{{-- `isolate` mantiene gli strati con z-index negativo dentro il contenitore;
     il velo conserva la leggibilità su immagini di sfondo arbitrarie. --}}
<div class="relative isolate mb-8">
    @if ($campaign->backgroundUrl())
        <div class="absolute inset-0 -z-10 bg-cover bg-center bg-no-repeat"
             style="background-image: url('{{ $campaign->backgroundUrl() }}')"></div>
        <div class="absolute inset-0 -z-10 bg-page" style="opacity: {{ $campaign->backgroundVeil() }}"></div>
    @endif

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

    <div class="text-center">
        @if ($campaign->coverUrl())
            <img src="{{ $campaign->coverUrl() }}" alt=""
                 class="mb-4 aspect-video w-full rounded-card border border-line object-cover">
        @endif

        <h2 class="text-2xl text-fg">{{ $campaign->title }}</h2>

        <p class="mt-1 flex flex-wrap items-center justify-center gap-2 text-sm text-muted">
            <span>Season {{ $campaign->season }}</span>

            @if ($campaign->hasQuestGiver())
                <span>·</span>
                <span>Capogilda {{ $campaign->quest_giver }}</span>
            @endif

            @unless ($campaign->isActive())
                <x-badge>Conclusa</x-badge>
            @endunless
        </p>

        @if ($campaign->dm)
            <p class="mt-1 text-sm text-muted">Conduce {{ $campaign->dm->name }}</p>
        @endif
    </div>

    @if ($lastSession)
        <x-card :href="route('sessions.show', $lastSession)">
            <p class="text-xs uppercase tracking-wide text-muted">L'ultima serata</p>
            <p class="mt-1 text-lg font-semibold text-fg">{{ $lastSession->displayTitle() }}</p>
            <p class="text-sm text-muted">{{ $lastSession->played_at->translatedFormat('j F Y') }}</p>

            @if ($lastSession->hasRecap())
                <p class="mt-3 whitespace-pre-line text-sm text-fg">{{ $lastSession->recap }}</p>
            @else
                <p class="mt-3 text-sm italic text-muted">Il resoconto non è ancora stato scritto.</p>
            @endif
        </x-card>
    @endif

    @if (filled($campaign->description) || $campaign->hasQuestGiver())
        <x-panel>
            @if (filled($campaign->description))
                <h3 class="text-lg font-semibold text-fg">La storia</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ $campaign->description }}</p>
            @endif

            @if ($campaign->hasQuestGiver())

                <div @class(['mt-4 border-t border-line pt-4' => filled($campaign->description)])>
                    <p class="text-xs uppercase tracking-wide text-muted">Il capogilda</p>

                    <div class="mt-3 flex gap-4">
                        @if ($campaign->questGiverPhotoUrl())
                            <img src="{{ $campaign->questGiverPhotoUrl() }}" alt="{{ $campaign->quest_giver }}"
                                 class="h-20 w-20 shrink-0 rounded-lg object-cover">
                        @endif

                        <div>
                            <p class="font-semibold text-fg">{{ $campaign->quest_giver }}</p>
                            @if (filled($campaign->quest_giver_description))
                                <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $campaign->quest_giver_description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </x-panel>
    @endif

    @if ($nextSession)
        <x-card :href="route('sessions.show', $nextSession)">
            <p class="text-xs uppercase tracking-wide text-muted">La prossima serata</p>
            <p class="mt-1 text-lg font-semibold text-fg">{{ $nextSession->displayTitle() }}</p>
            <p class="text-sm text-muted">
                {{ $nextSession->played_at->translatedFormat('l j F Y, H:i') }}
            </p>
        </x-card>
    @endif

    @if ($quests->isNotEmpty() || $questsConcluse > 0)
        <div>
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="\App\Enums\Icon::Quests" class="h-5 w-5" /> Le quest
            </h3>

            <div class="space-y-3">
                @forelse ($quests as $quest)
                    <x-quest-card :quest="$quest" :campaign="false" rewards />
                @empty
                    <x-empty>Nessuna quest aperta in questo momento.</x-empty>
                @endforelse
            </div>

            @if ($questsConcluse > 0)
                <p class="mt-3 text-center">
                    <a href="{{ route('ledger.index', ['campagna' => $campaign->slug]) }}"
                       class="text-sm text-muted hover:underline">
                        {{ $questsConcluse === 1
                            ? 'La quest conclusa di questo tavolo'
                            : 'Le '.$questsConcluse.' quest concluse di questo tavolo' }}
                    </a>
                </p>
            @endif
        </div>
    @endif

    @if ($maps->isNotEmpty())
        <div>
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="\App\Enums\Icon::Maps" class="h-5 w-5" /> Le mappe
            </h3>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($maps as $map)
{{-- Il titolo del link è già disponibile come testo, quindi l'immagine usa alt vuoto per evitare una lettura duplicata. --}}
                    <a href="{{ $map->url() }}" target="_blank" rel="noopener"
                       class="relative flex aspect-square items-center justify-center overflow-hidden
                              rounded-poster rounded-br-poster-cut border border-line p-3
                              transition hover:border-active">
                        <img src="{{ $map->url() }}" alt=""
                             class="absolute inset-0 h-full w-full object-cover">
                        <span class="absolute inset-0 bg-gradient-to-b from-black/75 via-black/40 to-black/75"></span>

                        <span class="relative text-center text-sm font-semibold text-white
                                     [text-shadow:0_1px_4px_rgb(0_0_0/0.6)]">{{ $map->title }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($characters->isNotEmpty())
        <div>
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="\App\Enums\Icon::Characters" class="h-5 w-5" /> La compagnia
            </h3>

            <div class="flex flex-wrap gap-2">
                @foreach ($characters as $character)
                    <a href="{{ route('characters.show', $character) }}"
                       class="rounded-full border border-line bg-surface px-3 py-1.5 text-sm text-fg transition hover:border-active">
                        {{ $character->name }}
                        <span class="text-muted">· {{ $character->user?->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($sessions->isNotEmpty())
        <div>
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="\App\Enums\Icon::Sessions" class="h-5 w-5" /> Le serate giocate
            </h3>

            <div class="grid grid-cols-2 gap-3">
                @foreach ($sessions as $session)
                    <x-card padding="sm" :href="route('sessions.show', $session)">
                        <p class="text-sm font-semibold text-fg">{{ $session->displayTitle() }}</p>
                        <p class="text-xs text-muted">
                            {{ $session->played_at->translatedFormat('j F Y') }}
                            @unless ($session->hasRecap())
                                · senza resoconto
                            @endunless
                        </p>
                    </x-card>
                @endforeach
            </div>
        </div>
    @endif

    <x-back :href="route('campaigns.index')">Torna alle campagne</x-back>
</div>
</div>
@endsection
