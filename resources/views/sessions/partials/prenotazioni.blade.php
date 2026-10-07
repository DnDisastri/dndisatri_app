{{-- Chi gioca questa sessione: posti (ospiti compresi), lista d'attesa, la propria prenotazione e i gesti del DM. --}}
@php
    use App\Enums\Icon;
    use App\Enums\SeatStatus;

    $gestisceOspiti = auth()->user()->can('manageGuests', $session);
    $campo = 'min-w-0 max-w-full flex-1 rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg';
@endphp

<x-panel>
    <div class="flex items-baseline justify-between gap-3">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
            <x-icona :is="Icon::Characters" class="h-5 w-5" /> Chi gioca
        </h3>
        <p class="shrink-0 text-sm text-muted">{{ $posti->count() }} / {{ $session->max_players }} posti</p>
    </div>

    {{-- Il minimo è un'indicazione: la conferma resta una scelta del DM. --}}
    <p class="mt-1 text-sm">
        @if ($session->isConfirmed())
            <span class="font-semibold text-fg">Sessione confermata.</span>
        @elseif ($session->missingToMinimum() > 0)
            <span class="text-muted">
                {{ $session->missingToMinimum() === 1 ? 'Manca 1 giocatore' : 'Mancano '.$session->missingToMinimum().' giocatori' }}
                per arrivare al minimo di {{ $session->min_players }}.
            </span>
        @else
            <span class="text-muted">Si può fare: manca solo che il DM la confermi.</span>
        @endif
    </p>

    @if ($posti->isNotEmpty())
        <ul class="mt-3 space-y-2">
            @foreach ($posti as $posto)
                <li class="text-sm">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                        <span class="min-w-0 text-fg">
                            {{ $posto->displayName() }}
                            @if ($posto->isGuest())
                                <span class="text-xs text-muted">· ospite</span>
                            @endif
                            @if ($posto->character)
                                <span class="text-muted">con</span>
                                <a href="{{ route('characters.show', $posto->character) }}" class="hover:underline">{{ $posto->character->name }}</a>
                            @elseif (filled($posto->guest_character))
                                <span class="text-muted">con {{ $posto->guest_character }}</span>
                            @endif
                        </span>
                        <span class="text-xs text-muted">{{ $posto->status->label() }}</span>
                    </div>

                    @if ($posto->isGuest() && $gestisceOspiti)
                        @include('sessions.partials.ospite-comandi', ['ospite' => $posto])
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-3 text-sm italic text-muted">Ancora nessuno.</p>
    @endif

    @if ($inAttesa->isNotEmpty())
        <div class="mt-4 border-t border-line pt-3">
            <p class="text-xs uppercase tracking-wide text-muted">In lista d'attesa, in ordine di arrivo</p>
            <ol class="mt-2 space-y-2">
                @foreach ($inAttesa as $inFila)
                    <li class="text-sm text-muted">
                        {{ $loop->iteration }}. {{ $inFila->displayName() }}@if ($inFila->isGuest()) <span class="text-xs">· ospite</span>@endif
                        @if ($inFila->isGuest() && $gestisceOspiti)
                            @include('sessions.partials.ospite-comandi', ['ospite' => $inFila])
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- La propria prenotazione. --}}
    @if (auth()->user()->can('book', $session) || auth()->user()->can('withdraw', $session))
        <div class="mt-4 border-t border-line pt-3">
            @if ($mioPosto?->isActive())
                <p class="text-sm font-semibold text-fg">{{ $mioPosto->mine() }}</p>
                <p class="mt-1 text-sm text-muted">
                    @if ($mioPosto === SeatStatus::Confirmed)
                        Il posto è tuo: ci vediamo alla sessione.
                    @elseif ($mioPosto === SeatStatus::Waiting)
                        Se qualcuno si tira indietro, il DM ti chiama.
                    @else
                        Il posto è tuo quando il DM conferma la sessione.
                    @endif
                </p>
            @endif

            @if ($mieiPersonaggi->isEmpty())
                <p class="text-sm text-muted">
                    Per prenotarti ti serve un personaggio.
                    <a href="{{ route('characters.create') }}" class="font-semibold text-active hover:underline">Crealo</a>
                </p>
            @else
                <form method="POST" action="{{ route('sessions.book', $session) }}" class="mt-3 flex flex-wrap items-center gap-2">
                    @csrf
                    <label for="character_id" class="sr-only">Con quale personaggio</label>
                    <select name="character_id" id="character_id" required class="{{ $campo }}">
                        @foreach ($mieiPersonaggi as $pg)
                            <option value="{{ $pg->id }}" @selected($mioPersonaggio === $pg->id)>{{ $pg->name }}</option>
                        @endforeach
                    </select>

                    <x-button :variant="$mioPosto?->isActive() ? 'secondary' : 'primary'">
                        @if ($mioPosto?->isActive())
                            Cambia personaggio
                        @else
                            {{ $session->isFull() ? 'Entro in lista d\'attesa' : 'Mi prenoto' }}
                        @endif
                    </x-button>
                </form>
            @endif

            @can('withdraw', $session)
                <form method="POST" action="{{ route('sessions.withdraw', $session) }}" class="mt-2">
                    @csrf
                    <x-button variant="quiet" size="sm">Mi tiro indietro</x-button>
                </form>
            @endcan
        </div>
    @endif

    {{-- I gesti del DM: confermare, chiamare dall'attesa, aggiungere un ospite. --}}
    @if (auth()->user()->can('confirmPlayers', $session) || auth()->user()->can('addGuest', $session) || (auth()->user()->can('promote', $session) && $inAttesa->isNotEmpty()))
        <div class="mt-4 space-y-4 border-t border-line pt-3">
            @can('confirmPlayers', $session)
                <form method="POST" action="{{ route('sessions.confirm', $session) }}">
                    @csrf
                    <x-button>Conferma la sessione</x-button>
                    <p class="mt-1 text-xs text-muted">
                        I prenotati ricevono la notifica che il posto è confermato; gli ospiti avvisali tu.
                        @unless ($session->hasMinimum())
                            Siete sotto il minimo di {{ $session->min_players }}.
                        @endunless
                    </p>
                </form>
            @endcan

            @can('promote', $session)
                @if ($inAttesa->isNotEmpty())
                    <form method="POST" action="{{ route('sessions.promote', $session) }}">
                        @csrf
                        <label for="booking_id" class="text-xs uppercase tracking-wide text-muted">Chiama dall'attesa</label>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <select name="booking_id" id="booking_id" required class="{{ $campo }}">
                                @foreach ($inAttesa as $inFila)
                                    <option value="{{ $inFila->id }}">{{ $inFila->displayName() }}{{ $inFila->isGuest() ? ' (ospite)' : '' }}</option>
                                @endforeach
                            </select>
                            <x-button variant="quiet">Fallo entrare</x-button>
                        </div>
                    </form>
                @endif
            @endcan

            @can('addGuest', $session)
                <details @if ($errors->has('guest_name')) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-fg">Aggiungi un ospite</summary>
                    <p class="mt-1 text-xs text-muted">Per chi si è prenotato fuori dall'app, per esempio su Instagram. Occupa un posto come gli altri.</p>

                    <form method="POST" action="{{ route('sessions.guests.store', $session) }}" class="mt-2 space-y-2">
                        @csrf
                        <label class="block text-sm">
                            <span class="mb-1 block text-muted">Nome</span>
                            <input type="text" name="guest_name" maxlength="80" required value="{{ old('guest_name') }}"
                                   placeholder="Marco (da Instagram)" class="{{ $campo }} w-full">
                            @error('guest_name') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-muted">Personaggio <span class="text-xs">(facoltativo)</span></span>
                            <input type="text" name="guest_character" maxlength="80" value="{{ old('guest_character') }}"
                                   placeholder="Pregenerato, guerriero" class="{{ $campo }} w-full">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-muted">Nota <span class="text-xs">(facoltativa, la vedono solo i DM)</span></span>
                            <input type="text" name="guest_note" maxlength="255" value="{{ old('guest_note') }}"
                                   placeholder="@marco su Instagram" class="{{ $campo }} w-full">
                        </label>
                        <x-button variant="secondary" size="sm">Aggiungi l'ospite</x-button>
                    </form>
                </details>
            @endcan
        </div>
    @endif
</x-panel>
