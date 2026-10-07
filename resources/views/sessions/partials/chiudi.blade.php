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
                Chi si è presentato. Partono spuntati i prenotati: togli chi non è venuto e aggiungi chi è arrivato. Il personaggio serve per le ricompense.
            </p>

            @php
                // Finché le presenze non sono segnate, si parte da chi ha un posto, con il suo personaggio.
                $daSegnare = $session->attendees->isEmpty() && $ospitiPresenti->isEmpty();
                $ospitiPrenotati = $session->bookings->filter(fn ($p) => $p->isGuest() && ($p->guest_attended || ($daSegnare && $p->status->takesSeat())));
                $tuttiGliOspiti = $session->bookings->filter(fn ($p) => $p->isGuest() && $p->status->isActive());
            @endphp

            <div class="mt-3 space-y-2">
                @foreach ($candidates as $candidato)
                    @php
                        if ($daSegnare) {
                            $posto = $posti->firstWhere('user_id', $candidato->id);
                            $presente = $posto !== null;
                            $scelto = $posto?->character_id;
                        } else {
                            $attendee = $session->attendees->firstWhere('id', $candidato->id);
                            $presente = $attendee !== null;
                            $scelto = $attendee?->pivot->character_id;
                        }
                    @endphp

                    <x-inset padding="sm" class="flex flex-wrap items-center justify-between gap-2">
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
                    ({{ $data['reason'] }}) da {{ $data['by'] }} a {{ implode(', ', $data['characters'] ?? []) }}.
                </p>
            @endforeach

            @if ($presentiConPg->isEmpty())
                <p class="mt-1 text-sm text-muted">Prima segna chi c'era, con il suo personaggio.</p>
            @else
                <form method="POST" action="{{ route('sessions.rewards', $session) }}" class="mt-2 space-y-3"
                      @if (! empty($session->rewards)) data-conferma="Le ricompense di questa sessione sono già state date. Le dai di nuovo?" @endif>
                    @csrf

                    <p class="text-xs text-muted">
                        Le stesse monete a ciascuno dei {{ $presentiConPg->count() }} personaggi presenti. Nel Registro di ognuno resta una riga.
                    </p>

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

            <textarea name="recap" id="recap" rows="10" maxlength="20000"
                      class="mt-1 {{ $campo }}"
                      placeholder="Cosa è successo, chi ha fatto cosa, com'è finita.">{{ old('recap', $session->recap) }}</textarea>

            @error('recap')
                <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
            @enderror

            <x-button class="mt-2">Salva il resoconto</x-button>
        </form>
    @endcan
</x-panel>
