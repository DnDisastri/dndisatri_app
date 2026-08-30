@extends('layouts.app')
@section('title', $quest->title)

@section('content')

@php
    use App\Enums\Icon;
    use App\Enums\QuestSeatStatus;

    $campagna = $quest->campaign;
    $conduce = auth()->user()->can('conclude', $quest)
        || auth()->user()->can('confirmNight', $quest)
        || auth()->user()->can('promote', $quest);
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

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

                @if ((int) $quest->reward_gold > 0)
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-medium text-fg">
                        <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" />
                        {{ $quest->reward_gold }} mo
                    </p>
                @endif

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

    <x-panel>
        <div class="flex items-baseline justify-between gap-3">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="Icon::Characters" class="h-5 w-5" /> Chi c'è
            </h3>

            <p class="text-sm text-muted">
                {{ $quest->participantCount() }} / {{ $quest->max_participants }} posti
            </p>
        </div>

{{-- Il minimo è informativo: indica se la serata è sostenibile, ma la conferma resta una decisione del DM. --}}
        @if ($quest->isActive())
            <p class="mt-1 text-sm">
                @if ($quest->isNightConfirmed())
                    <span class="font-semibold text-fg">La serata si fa.</span>
                    <span class="text-muted">I posti sono confermati.</span>
                @elseif ($quest->missingToMinimum() > 0)
                    <span class="text-muted">
{{-- Singolare e plurale sono gestiti esplicitamente perché l'inflector di Laravel è orientato all'inglese. --}}
                        @if ($quest->missingToMinimum() === 1)
                            Manca 1 giocatore.
                        @else
                            Mancano {{ $quest->missingToMinimum() }} giocatori.
                        @endif
                    </span>
                @else
                    <span class="text-muted">Si può fare: manca solo che il dungeon master lo dica.</span>
                @endif
            </p>
        @endif

        @if ($seatHolders->isNotEmpty())
            <ul class="mt-3 space-y-1">
                @foreach ($seatHolders as $partecipante)
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-fg">{{ $partecipante->name }}</span>
                        <span class="text-xs text-muted">
                            {{ QuestSeatStatus::from($partecipante->pivot->status)->label() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm italic text-muted">Ancora nessuno.</p>
        @endif
{{-- La lista d'attesa resta separata dai partecipanti perché non occupa posti. --}}
        @if ($waiting->isNotEmpty())
            <div class="mt-4 border-t border-line pt-3">
                <p class="text-xs uppercase tracking-wide text-muted">
                    In lista d'attesa, in ordine di arrivo
                </p>
                <ol class="mt-2 space-y-1">
                    @foreach ($waiting as $inFila)
                        <li class="text-sm text-muted">{{ $loop->iteration }}. {{ $inFila->name }}</li>
                    @endforeach
                </ol>
            </div>
        @endif
    </x-panel>

    @if ($quest->isActive())
        <x-panel>
            @if ($mioPosto?->takesSeat())
{{-- `mine()` formula lo stato in seconda persona; `label()` resta per gli altri partecipanti. --}}
                <p class="text-sm font-semibold text-fg">{{ $mioPosto->mine() }}</p>
                <p class="mt-1 text-sm text-muted">
                    @if ($mioPosto === QuestSeatStatus::Confirmed)
                        Il posto è tuo: ci vediamo al tavolo.
                    @else
                        Il posto è tuo quando il dungeon master conferma che la serata si fa.
                    @endif
                </p>
            @elseif ($mioPosto === QuestSeatStatus::Waiting)
                <p class="text-sm font-semibold text-on-accent-soft">Sei in lista d'attesa</p>
                <p class="mt-1 text-sm text-muted">
                    Se qualcuno si tira indietro, il dungeon master ti chiama.
                </p>
            @endif

            <div class="mt-3 flex flex-wrap gap-2">
                @can('withdraw', $quest)
                    <form method="POST" action="{{ route('quests.withdraw', $quest) }}">
                        @csrf
                        <x-button variant="quiet">Mi tiro indietro</x-button>
                    </form>
                @endcan

                @can('book', $quest)
                    <form method="POST" action="{{ route('quests.book', $quest) }}">
                        @csrf

                        <x-button>
                            {{ $quest->isFull() ? 'Entro in lista d\'attesa' : 'Voglio partecipare' }}
                        </x-button>
                    </form>
                @endcan
            </div>
        </x-panel>
    @endif

    @if ($conduce && $quest->isActive())
{{-- Usa `ring` invece di una seconda classe `border-*` per evitare conflitti di precedenza nel CSS compilato. --}}
        <x-panel class="ring-1 ring-active">
            <h3 class="text-lg font-semibold text-fg">Conduci tu</h3>

            @can('confirmNight', $quest)
                <form method="POST" action="{{ route('quests.confirm-night', $quest) }}" class="mt-3">
                    @csrf
                    <x-button>La serata si fa</x-button>
                    <span class="ml-2 text-xs text-muted">
                        Tutti i prenotati ricevono la notifica che il posto è confermato.
                        @unless ($quest->hasMinimum())
                            Siete sotto il minimo di {{ $quest->min_participants }}.
                        @endunless
                    </span>
                </form>
            @endcan

            @can('promote', $quest)
                @if ($waiting->isNotEmpty())
                    <form method="POST" action="{{ route('quests.promote', $quest) }}"
                          class="mt-4 border-t border-line pt-3">
                        @csrf
                        <label for="user_id" class="text-xs uppercase tracking-wide text-muted">
                            Chiama dall'attesa
                        </label>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <select name="user_id" id="user_id" required
                                    class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg">
                                @foreach ($waiting as $inFila)
                                    <option value="{{ $inFila->id }}">{{ $inFila->name }}</option>
                                @endforeach
                            </select>

                            <x-button variant="quiet">Fallo entrare</x-button>
                        </div>
                    </form>
                @endif
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
                                Completata — è andata a buon fine
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="outcome" value="closed">
                                Chiusa — l'abbiamo lasciata perdere
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
@endsection
