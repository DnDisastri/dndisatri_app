{{-- Chi gioca: i confermati per tutti, la propria richiesta, e per il DM tutte le richieste coi gesti sui posti. --}}
@php
    use App\Enums\Icon;
    use App\Enums\SeatStatus;

    $utente = auth()->user();
    $gestisce = $utente->can('manageSeats', $session);
    $gestisceOspiti = $utente->can('manageGuests', $session);
    $stato = $mioPosto?->status;
    $campo = 'min-w-0 max-w-full flex-1 rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg';
@endphp

<x-panel>
    <div class="flex items-baseline justify-between gap-3">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
            <x-icona :is="Icon::Characters" class="h-5 w-5" /> Chi gioca
        </h3>
        <p class="shrink-0 text-sm text-muted">{{ $confermati->count() }} / {{ $session->max_players }} confermati</p>
    </div>

    {{-- Il minimo è un'indicazione per il DM, non blocca niente. --}}
    @if ($session->missingToMinimum() > 0)
        <p class="mt-1 text-sm text-muted">
            {{ $session->missingToMinimum() === 1 ? 'Manca 1 giocatore' : 'Mancano '.$session->missingToMinimum().' giocatori' }}
            per arrivare al minimo di {{ $session->min_players }}.
        </p>
    @endif

    @if ($confermati->isNotEmpty())
        <ul class="mt-3 space-y-2">
            @foreach ($confermati as $posto)
                <li class="text-sm">
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
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-3 text-sm italic text-muted">Ancora nessun posto confermato.</p>
    @endif

    {{-- La propria richiesta: cosa succede adesso e cosa si può fare. --}}
    @if ($utente->can('book', $session) || $utente->can('withdraw', $session))
        <div class="mt-4 space-y-3 border-t border-line pt-3">
            @if ($stato?->isActive())
                <div>
                    <p class="text-sm font-semibold text-fg">{{ $stato->mine() }}</p>
                    <p class="mt-1 text-sm text-muted">
                        @switch($stato)
                            @case(SeatStatus::Offered)
                                C'è un posto per te: è tuo se confermi entro
                                {{ $mioPosto->offer_expires_at->translatedFormat('l j F \\a\\l\\l\\e H:i') }}.
                                @break
                            @case(SeatStatus::Confirmed)
                                Il posto è tuo: ci vediamo alla sessione.
                                @break
                            @case(SeatStatus::Reserve)
                                Se si libera un posto, potresti essere chiamato. Non è garantito.
                                @break
                            @case(SeatStatus::Expired)
                                Non hai confermato in tempo e il posto è tornato libero. Potrebbe esserti offerto di nuovo.
                                @break
                            @default
                                @if ($mioPosto->awaitsReserveAnswer())
                                    I posti sono tutti confermati. Vuoi restare fra le riserve? Se qualcuno si ritira potresti essere chiamato, ma non è garantito.
                                @else
                                    Hai chiesto un posto per questa sessione. Se c'è posto per te, ti arriverà un'email per confermarlo.
                                @endif
                        @endswitch
                    </p>
                </div>

                @if ($stato === SeatStatus::Offered)
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('sessions.answer-offer', $session) }}">
                            @csrf
                            <input type="hidden" name="risposta" value="si">
                            <x-button>Confermo il posto</x-button>
                        </form>
                        <form method="POST" action="{{ route('sessions.answer-offer', $session) }}"
                              data-conferma="Rinunci al posto? Passa a qualcun altro." data-conferma-azione="Rinuncio">
                            @csrf
                            <input type="hidden" name="risposta" value="no">
                            <x-button variant="quiet">Rinuncio</x-button>
                        </form>
                    </div>
                @elseif ($mioPosto->awaitsReserveAnswer())
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('sessions.answer-reserve', $session) }}">
                            @csrf
                            <input type="hidden" name="risposta" value="si">
                            <x-button>Resto fra le riserve</x-button>
                        </form>
                        <form method="POST" action="{{ route('sessions.answer-reserve', $session) }}">
                            @csrf
                            <input type="hidden" name="risposta" value="no">
                            <x-button variant="quiet">No, grazie</x-button>
                        </form>
                    </div>
                @endif
            @endif

            @if ($mieiPersonaggi->isEmpty())
                <p class="text-sm text-muted">
                    Per chiedere un posto ti serve un personaggio.
                    <a href="{{ route('characters.create') }}" class="font-semibold text-active hover:underline">Crealo</a>
                </p>
            @else
                <form method="POST" action="{{ route('sessions.book', $session) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <label for="character_id" class="sr-only">Con quale personaggio</label>
                    <select name="character_id" id="character_id" required class="{{ $campo }}">
                        @foreach ($mieiPersonaggi as $pg)
                            <option value="{{ $pg->id }}" @selected($mioPersonaggio === $pg->id)>{{ $pg->name }}</option>
                        @endforeach
                    </select>

                    <x-button :variant="$stato?->isActive() ? 'secondary' : 'primary'">
                        {{ $stato?->isActive() ? 'Cambia personaggio' : 'Chiedo un posto' }}
                    </x-button>
                </form>

                @unless ($stato?->isActive())
                    <p class="text-xs text-muted">
                        Puoi chiedere un posto anche se la sessione sembra piena.
                        Se c'è posto per te, ti arriverà un'email per confermarlo.
                    </p>
                @endunless
            @endif

            {{-- Davanti a una domanda («Confermo/Rinuncio», «Resto/No, grazie») c'è già il modo di dire no. --}}
            @if ($utente->can('withdraw', $session) && $stato !== SeatStatus::Offered && ! $mioPosto?->awaitsReserveAnswer())
                <form method="POST" action="{{ route('sessions.withdraw', $session) }}"
                      @if ($stato === SeatStatus::Confirmed) data-conferma="Lasci la sessione? Il posto torna libero." data-conferma-azione="Lascio il posto" @endif>
                    @csrf
                    <x-button variant="quiet" size="sm">Mi tiro indietro</x-button>
                </form>
            @endif
        </div>
    @endif

    {{-- Il DM: tutte le richieste in ordine di arrivo, e sceglie chi vuole. --}}
    @if ($gestisce)
        <div class="mt-4 border-t border-line pt-3">
            <p class="text-xs uppercase tracking-wide text-muted">Richieste, in ordine di arrivo</p>
            <p class="mt-1 text-xs text-muted">
                Solo i DM le vedono. Chi scegli riceve un'email e ha {{ \App\Models\SessionBooking::OFFER_HOURS }} ore per confermare.
                {{ $session->freeSlots() === 1 ? 'Resta 1 posto da offrire.' : 'Restano '.$session->freeSlots().' posti da offrire.' }}
            </p>

            @if ($richieste->isEmpty())
                <p class="mt-2 text-sm italic text-muted">Nessuna richiesta.</p>
            @else
                <ol class="mt-2 space-y-3">
                    @foreach ($richieste as $richiesta)
                        @php $contatti = $richiesta->status === SeatStatus::Reserve || $richiesta->isGuest(); @endphp
                        <li class="text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <span class="min-w-0 text-fg">
                                    {{ $loop->iteration }}. {{ $richiesta->displayName() }}
                                    @if ($richiesta->isGuest())
                                        <span class="text-xs text-muted">· ospite</span>
                                    @endif
                                    @if ($richiesta->characterName())
                                        <span class="text-muted">con {{ $richiesta->characterName() }}</span>
                                    @endif
                                </span>
                                <span class="flex shrink-0 flex-col items-end gap-0.5">
                                    {{-- «Riserva?»: gli è stata fatta la domanda e non ha ancora risposto. --}}
                                    <x-badge :tone="$richiesta->status->tone()">
                                        {{ $richiesta->awaitsReserveAnswer() ? 'Riserva?' : $richiesta->status->label() }}
                                    </x-badge>
                                    @if ($richiesta->status === SeatStatus::Offered)
                                        <span class="text-xs text-muted">entro {{ $richiesta->offer_expires_at->translatedFormat('j M, H:i') }}</span>
                                    @endif
                                </span>
                            </div>

                            @if ($contatti)
                                <p class="mt-1 break-words text-xs text-muted">
                                    {{ collect([$richiesta->contactSocial(), $richiesta->contactEmail(), $richiesta->contactPhone()])->filter()->implode(' · ') ?: 'Nessun contatto' }}
                                </p>
                            @endif

                            @if ($richiesta->status->canBeOffered() && ! $session->isFull())
                                <form method="POST" action="{{ route('sessions.offer', $session) }}" class="mt-1">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $richiesta->id }}">
                                    <x-button variant="secondary" size="sm">Offri il posto</x-button>
                                </form>
                            @endif

                            @if ($richiesta->isGuest() && $gestisceOspiti)
                                @include('sessions.partials.ospite-comandi', ['ospite' => $richiesta])
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="mt-4 space-y-2 border-t border-line pt-3">
            <p class="text-xs uppercase tracking-wide text-muted">Link per chi non ha un account</p>
            <p class="text-xs text-muted">Da mandare a chi vuole giocare senza registrarsi: chiede il posto con nome ed email.</p>
            <input type="text" readonly value="{{ route('guest-bookings.create', $session) }}"
                   aria-label="Link per chiedere un posto senza account"
                   class="{{ $campo }} w-full">
        </div>
    @endif

    @can('addGuest', $session)
        <div class="mt-4 border-t border-line pt-3">
            <details @if ($errors->hasAny(['guest_name', 'guest_social', 'guest_email'])) open @endif>
                <summary class="cursor-pointer text-sm font-semibold text-fg">Aggiungi un ospite</summary>
                <p class="mt-1 text-xs text-muted">
                    Per chi si è messo d'accordo con te fuori dall'app, per esempio su Instagram o Telegram.
                    Entra col posto confermato, o fra le riserve se è pieno.
                </p>

                <form method="POST" action="{{ route('sessions.guests.store', $session) }}" class="mt-2 space-y-2">
                    @csrf
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Nome</span>
                        <input type="text" name="guest_name" maxlength="80" required value="{{ old('guest_name') }}"
                               placeholder="Marco" class="{{ $campo }} w-full">
                        @error('guest_name') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Instagram o Telegram</span>
                        <input type="text" name="guest_social" maxlength="80" value="{{ old('guest_social') }}"
                               placeholder="@marco su Instagram" class="{{ $campo }} w-full">
                        @error('guest_social') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Email <span class="text-xs">(facoltativa: gli arriva il link per disdire)</span></span>
                        <input type="email" name="guest_email" maxlength="255" value="{{ old('guest_email') }}"
                               class="{{ $campo }} w-full">
                        @error('guest_email') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Telefono <span class="text-xs">(facoltativo)</span></span>
                        <input type="tel" name="guest_phone" maxlength="30" value="{{ old('guest_phone') }}"
                               class="{{ $campo }} w-full">
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Personaggio <span class="text-xs">(facoltativo)</span></span>
                        <input type="text" name="guest_character" maxlength="80" value="{{ old('guest_character') }}"
                               placeholder="Pregenerato, guerriero" class="{{ $campo }} w-full">
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Nota <span class="text-xs">(facoltativa, la vedono solo i DM)</span></span>
                        <input type="text" name="guest_note" maxlength="255" value="{{ old('guest_note') }}"
                               class="{{ $campo }} w-full">
                    </label>
                    <x-button variant="secondary" size="sm">Aggiungi l'ospite</x-button>
                </form>
            </details>
        </div>
    @endcan
</x-panel>
