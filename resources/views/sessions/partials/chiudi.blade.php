{{-- Tre passi in ordine: chi c'era, cosa ricevono, cosa è successo. Li chiude anche un DM che copre un collega. --}}
@php
    $sostituisci = $session->isSubstitute(auth()->user());
    $presentiConPg = $session->attendees->filter(fn ($p) => $p->pivot->character_id !== null);
    $campo = 'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg';
@endphp

{{-- `ring` evidenzia il pannello senza una seconda classe `border-*` concorrente. --}}
<x-panel class="ring-1 ring-active">
    <h3 class="text-lg font-semibold text-fg">Chiudi la sessione</h3>

    @if ($sostituisci)
        <p class="mt-1 text-xs text-muted">
            Stai coprendo {{ $session->campaign->dm?->name ?? 'il DM della campagna' }}: riceverà un avviso di quello che salvi.
        </p>
    @endif

    @can('recordAttendance', $session)
        <form method="POST" action="{{ route('sessions.attendance', $session) }}" class="mt-4">
            @csrf

            <p class="text-xs uppercase tracking-wide text-muted">1. Chi c'era</p>
            <p class="mt-1 text-xs text-muted">
                Chi si è presentato. Partono spuntati i confermati: togli chi non è venuto, e cerca chi è arrivato in più. Il personaggio serve per le ricompense.
            </p>

            @php
                // Finché le presenze non sono segnate, si parte dai confermati, col loro personaggio.
                $daSegnare = $session->attendees->isEmpty() && $ospitiPresenti->isEmpty();
                $ospitiPrenotati = $session->bookings->filter(fn ($p) => $p->isGuest() && ($p->guest_attended || ($daSegnare && $p->status === \App\Enums\SeatStatus::Confirmed)));
                $tuttiGliOspiti = $session->bookings->filter(fn ($p) => $p->isGuest() && $p->status->isActive());

                $righe = $candidates->map(function ($candidato) use ($daSegnare, $confermati, $session) {
                    if ($daSegnare) {
                        $posto = $confermati->firstWhere('user_id', $candidato->id);

                        return ['utente' => $candidato, 'presente' => $posto !== null, 'scelto' => $posto?->character_id, 'tavolo' => $posto?->session_table_id];
                    }

                    $attendee = $session->attendees->firstWhere('id', $candidato->id);

                    return ['utente' => $candidato, 'presente' => $attendee !== null, 'scelto' => $attendee?->pivot->character_id, 'tavolo' => $attendee?->pivot->session_table_id];
                });

                // Con 60 giocatori la lista intera non si scorre: a vista solo gli spuntati, gli altri si cercano.
                [$aVista, $daCercare] = $righe->partition(fn ($r) => $r['presente']);
            @endphp

            <div class="mt-3 space-y-2">
                @foreach ($aVista->concat($daCercare) as $riga)
                    @php
                        ['utente' => $candidato, 'presente' => $presente, 'scelto' => $scelto, 'tavolo' => $tavoloScelto] = $riga;
                    @endphp

                    {{-- La ricerca sta fra gli spuntati e i risultati. --}}
                    @if ($loop->index === $aVista->count())
                        <label class="block pt-1 text-sm">
                            <span class="mb-1 block text-muted">Aggiungi un altro giocatore</span>
                            <input type="search" data-presenze-cerca placeholder="Scrivi il nome" autocomplete="off"
                                   class="{{ $campo }}">
                        </label>
                    @endif

                    <x-inset padding="sm" class="flex flex-wrap items-center justify-between gap-2"
                             :data-presenza-extra="$presente ? null : mb_strtolower($candidato->name)"
                             :hidden="! $presente">
                        <label class="flex items-center gap-2 text-sm text-fg">
                            <input type="checkbox" name="presenti[]" value="{{ $candidato->id }}"
                                   @checked($presente)
                                   class="rounded border-line accent-[var(--ui-active)]">
                            {{ $candidato->name }}
                        </label>
                        {{-- Solo i personaggi del partecipante; la regola è controllata anche lato server. --}}
                        @if ($candidato->characters->isNotEmpty())
                            <select name="personaggi[{{ $candidato->id }}]" aria-label="Personaggio di {{ $candidato->name }}"
                                    class="max-w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">
                                <option value="">Senza personaggio</option>
                                @foreach ($candidato->characters as $personaggio)
                                    <option value="{{ $personaggio->id }}" @selected($scelto === $personaggio->id)>
                                        {{ $personaggio->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @if ($session->tables->isNotEmpty())
                            <select name="tavoli[{{ $candidato->id }}]" aria-label="Tavolo di {{ $candidato->name }}"
                                    class="max-w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">
                                <option value="">Senza tavolo</option>
                                @foreach ($session->tables as $tavolo)
                                    <option value="{{ $tavolo->id }}" @selected($tavoloScelto === $tavolo->id)>{{ $tavolo->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </x-inset>
                @endforeach

                @foreach ($tuttiGliOspiti as $ospite)
                    <x-inset padding="sm" class="flex flex-wrap items-center justify-between gap-2">
                        <label class="flex items-center gap-2 text-sm text-fg">
                            <input type="checkbox" name="ospiti[]" value="{{ $ospite->id }}"
                                   @checked($ospitiPrenotati->contains('id', $ospite->id))
                                   class="rounded border-line accent-[var(--ui-active)]">
                            {{ $ospite->guest_name }} <span class="text-xs text-muted">ospite</span>
                        </label>
                        @if (filled($ospite->guest_character))
                            <span class="text-xs text-muted">{{ $ospite->guest_character }}</span>
                        @endif
                    </x-inset>
                @endforeach
            </div>

            <p class="mt-2 text-xs text-muted">Gli ospiti non ricevono ricompense: non hanno un personaggio nell'app. L'elenco si sostituisce: quello che salvi è la lista definitiva, correzioni comprese.</p>

            <x-button variant="secondary" class="mt-2">Salva le presenze</x-button>
        </form>

        <div class="mt-5 border-t border-line pt-4">
            <p class="text-xs uppercase tracking-wide text-muted">2. Ricompense</p>

            @foreach ($session->rewards ?? [] as $data)
                <p class="mt-1 text-xs text-muted">
                    Già date: {{ \App\Domain\Dnd\Coins::fromArray($data['coins'] ?? [])->format() }} a testa
                    ({{ $data['reason'] }}) da {{ $data['by'] }} a {{ implode(', ', $data['characters'] ?? []) }}@if (! empty($data['table'])) ({{ $data['table'] }})@endif.
                </p>
            @endforeach

            @if ($presentiConPg->isEmpty())
                <p class="mt-1 text-sm text-muted">Prima segna chi c'era, con il suo personaggio.</p>
            @else
                <form method="POST" action="{{ route('sessions.rewards', $session) }}" class="mt-2 space-y-3"
                      @if (! empty($session->rewards)) data-conferma="Le ricompense di questa sessione sono già state date. Le dai di nuovo?" data-conferma-azione="Dalle di nuovo" @endif>
                    @csrf

                    <p class="text-xs text-muted">
                        Le stesse monete a ciascuno di quelli che scegli. Nel Registro di ognuno resta una riga.
                    </p>

                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">A chi</span>
                        <select name="destinatari" class="{{ $campo }}">
                            <option value="tutti">Tutti i {{ $presentiConPg->count() }} personaggi presenti</option>
                            @foreach ($session->tables as $tavolo)
                                <option value="tavolo-{{ $tavolo->id }}" @selected(old('destinatari') === 'tavolo-'.$tavolo->id)>I presenti del {{ $tavolo->name }}</option>
                            @endforeach
                            @foreach ($presentiConPg as $presente)
                                @php $pg = $presente->characters->firstWhere('id', $presente->pivot->character_id); @endphp
                                @if ($pg)
                                    <option value="pg-{{ $pg->id }}" @selected(old('destinatari') === 'pg-'.$pg->id)>Solo {{ $pg->name }} ({{ $presente->name }})</option>
                                @endif
                            @endforeach
                        </select>
                    </label>

                    <x-campo-monete name="coins" label="Monete a testa" />

                    <label class="block text-sm">
                        <span class="mb-1 block text-muted">Motivo</span>
                        <input type="text" name="reason" maxlength="120" required value="{{ old('reason') }}"
                               placeholder="Taglia sul capo dei briganti" class="{{ $campo }}">
                    </label>
                    @error('reason') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror
                    @error('coins') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror

                    <x-button variant="secondary">Dai le ricompense</x-button>
                </form>
            @endif
        </div>
    @endcan

    @can('writeRecap', $session)
        <form method="POST" action="{{ route('sessions.recap', $session) }}" class="mt-5 border-t border-line pt-4">
            @csrf

            <label for="recap" class="text-xs uppercase tracking-wide text-muted">
                3. {{ $session->hasRecap() ? 'Correggi il resoconto' : 'Scrivi il resoconto' }}
            </label>

            <x-testo-contato name="recap" id="recap" rows="10" :max="\App\Models\GameSession::recapLimit($session->recap)"
                             :value="old('recap', $session->recap)" class="mt-1 {{ $campo }}"
                             placeholder="Cosa è successo, chi ha fatto cosa, com'è finita." />

            @error('recap')
                <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
            @enderror

            <x-button class="mt-2">Salva il resoconto</x-button>
        </form>
    @endcan
</x-panel>
