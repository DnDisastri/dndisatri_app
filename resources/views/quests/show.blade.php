@extends('layouts.app')
@section('title', $quest->title)

@section('content')

@php
    use App\Enums\Icon;

    $campagna = $quest->campaign;
    $conduce = auth()->user()->can('conclude', $quest) || auth()->user()->can('schedule', $quest);
@endphp

<x-pagina class="space-y-6">

    <div class="space-y-4">

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('quests.index') }}"
               class="group inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-fg">
                <x-icona :is="Icon::Back" class="h-4 w-4 shrink-0" />
                <span class="group-hover:underline">Torna alle quest</span>
            </a>

            <a href="{{ route('campaigns.show', $campagna) }}"
               class="group inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-fg">
                <span class="group-hover:underline">{{ $campagna->title }}</span>
                <x-icona :is="Icon::GoTo" class="h-4 w-4 shrink-0" />
            </a>
        </div>

        <div class="flex items-center justify-end gap-1.5">
            @if ($quest->type)
                <x-badge tone="neutral">{{ $quest->type->label() }}</x-badge>
            @endif

            @if ($quest->difficulty)
                <x-badge tone="accent">{{ $quest->difficulty->label() }}</x-badge>
            @endif
        </div>

        <div class="text-center">
            <h2 class="text-2xl text-fg">{{ $quest->title }}</h2>

            <p class="mt-1 flex flex-wrap items-center justify-center gap-2 text-sm text-muted">
                @if ($campagna->dm)
                    <span>Conduce {{ $campagna->dm->name }}</span>
                @endif
                @unless ($quest->isActive())
                    <span>·</span>
                    <x-badge>{{ $quest->outcome()->label() }}</x-badge>
                @endunless
            </p>
        </div>
    </div>

    <div class="space-y-6 lg:grid lg:grid-cols-2 lg:items-start lg:gap-6 lg:space-y-0">
    <div class="space-y-6">
    @unless ($quest->isActive())
        <x-panel>
            <p class="text-xs uppercase tracking-wide text-muted">Com'è andata</p>

            @if (filled($quest->outcome_notes))
                <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ $quest->outcome_notes }}</p>
            @else
                <p class="mt-2 text-sm italic text-muted">

                    La quest è {{ mb_strtolower($quest->outcome()->label()) }}, ma nessuno ha
                    raccontato come.
                </p>
            @endif

            <x-reactions :for="$quest" class="mt-4 border-t border-line pt-3" />
        </x-panel>
    @endunless

    @if ($campagna->hasQuestGiver())
        <x-panel>
            <p class="text-xs uppercase tracking-wide text-muted">La quest la affida</p>

            <div class="mt-3 flex items-center gap-4">
                @if ($campagna->questGiverPhotoUrl())
                    <img src="{{ $campagna->questGiverPhotoUrl() }}" alt="{{ $campagna->quest_giver }}"
                         class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @endif

                <p class="font-semibold text-fg">{{ $campagna->quest_giver }}</p>
            </div>
        </x-panel>
    @endif

    <x-panel>
        <p class="whitespace-pre-line text-sm text-fg">{{ $quest->description }}</p>

        @if (filled($quest->setting))
            <div class="mt-4 border-t border-line pt-3">
                <p class="text-xs uppercase tracking-wide text-muted">Dove</p>
                <p class="mt-1 whitespace-pre-line text-sm text-fg">{{ $quest->setting }}</p>
            </div>
        @endif

        @if ($quest->hasReward())

            <div class="mt-4 border-t border-line pt-3">
                <p class="text-xs uppercase tracking-wide text-muted">Ricompense</p>

                @unless ($quest->rewardCoins()->isEmpty())
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-medium text-fg">
                        <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" />
                        <x-monete :borsa="$quest->rewardCoins()" />
                    </p>
                @endunless

                @if (filled($quest->reward_items))
                    <ul class="mt-1 list-inside list-disc text-sm text-fg">
                        @foreach ($quest->reward_items as $oggetto)
                            <li>{{ $oggetto }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (filled($quest->rewards))
                    <p class="mt-1 whitespace-pre-line text-sm text-fg">{{ $quest->rewards }}</p>
                @endif
            </div>
        @endif
    </x-panel>

    </div>

    <div class="space-y-6">
    <x-panel>
        <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
            <x-icona :is="Icon::Sessions" class="h-5 w-5" /> Quando si gioca
        </h3>

        @if ($quest->isScheduled())
            <p class="mt-2 text-sm text-fg">
                Nella sessione di {{ $quest->session->played_at->translatedFormat('l j F, H:i') }}.
            </p>
            <x-button size="sm" class="mt-3" :href="route('sessions.show', $quest->session)">Vai alla sessione e prenotati</x-button>
        @elseif ($quest->isActive())
            <p class="mt-2 text-sm text-muted">
                Il DM non l'ha ancora messa in una sessione. Se ti interessa, segnala: quando la mette, ti arriverà un avviso.
            </p>
        @endif

        @if ($quest->isActive())
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p class="text-sm text-muted">
                    {{ $interessati->count() === 1 ? 'Interessa a 1 giocatore' : 'Interessa a '.$interessati->count().' giocatori' }}
                </p>

                @can('interest', $quest)
                    <form method="POST" action="{{ route('quests.interest', $quest) }}">
                        @csrf
                        <x-button :variant="$miInteressa ? 'quiet' : 'primary'" size="sm">
                            {{ $miInteressa ? 'Non mi interessa più' : 'Mi interessa' }}
                        </x-button>
                    </form>
                @endcan
            </div>

            @if ($interessati->isNotEmpty())
                <p class="mt-2 text-sm text-fg">{{ $interessati->pluck('name')->join(', ', ' e ') }}</p>
            @endif
        @endif
    </x-panel>

    @if ($conduce && $quest->isActive())
{{-- Usa `ring` invece di una seconda classe `border-*` per evitare conflitti di precedenza nel CSS compilato. --}}
        <x-panel class="ring-1 ring-active">
            <h3 class="text-lg font-semibold text-fg">Conduci tu</h3>

            @can('schedule', $quest)
                <form method="POST" action="{{ route('quests.schedule', $quest) }}" class="mt-3">
                    @csrf
                    <label for="game_session_id" class="text-xs uppercase tracking-wide text-muted">In quale sessione si gioca</label>

                    @if ($sessioni->isEmpty() && ! $quest->isScheduled())
                        <p class="mt-1 text-sm text-muted">Nessuna sessione in programma per questa campagna: creala dal Pannello.</p>
                    @else
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <select name="game_session_id" id="game_session_id"
                                    class="min-w-0 max-w-full flex-1 rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg">
                                <option value="">Non ancora</option>
                                @foreach ($sessioni as $sessione)
                                    <option value="{{ $sessione->id }}" @selected($quest->game_session_id === $sessione->id)>
                                        {{ $sessione->played_at->translatedFormat('D j F, H:i') }}{{ filled($sessione->title) ? ' · '.$sessione->title : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-button variant="secondary">Salva</x-button>
                        </div>
                        <p class="mt-1 text-xs text-muted">Chi l'ha segnata con «Mi interessa» riceve un avviso.</p>
                    @endif
                </form>
            @endcan
{{-- La conclusione è irreversibile; il form resta dietro `<details>` per ridurre attivazioni accidentali. --}}
            @can('conclude', $quest)
                <details class="mt-4 border-t border-line pt-3">
                    <summary class="cursor-pointer text-sm text-muted">Concludi la quest</summary>

                    <form method="POST" action="{{ route('quests.conclude', $quest) }}" class="mt-3 space-y-3">
                        @csrf

                        <div class="space-y-1 text-sm text-fg">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="outcome" value="completed" checked>
                                Completata: è andata a buon fine
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="outcome" value="closed">
                                Chiusa: l'abbiamo lasciata perdere
                            </label>
                        </div>

                        <div>
                            <label for="outcome_notes" class="text-xs uppercase tracking-wide text-muted">
                                Com'è andata
                            </label>
                            <textarea name="outcome_notes" id="outcome_notes" rows="3" maxlength="2000"
                                      placeholder="Due righe che i partecipanti leggeranno qui sopra."
                                      class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg">{{ old('outcome_notes') }}</textarea>
                        </div>

                        <p class="text-xs text-muted">
                            Non si torna indietro: completata e chiusa sono definitive.
                        </p>

                        <x-button variant="quiet">Concludi</x-button>
                    </form>
                </details>
            @endcan
        </x-panel>
    @endif
    </div>
    </div>
</x-pagina>
@endsection
