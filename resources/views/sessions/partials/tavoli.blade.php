{{-- I tavoli di una sessione speciale: chi conduce, chi gioca dove, e il resoconto di ognuno. --}}
@php
    use App\Enums\Icon;

    $daGiocare = $session->isUpcoming();
    $gestisce = auth()->user()->can('manageSeats', $session);
    $scrive = auth()->user()->can('writeRecap', $session);
    $campo = 'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg';

    // Prima della sessione contano le assegnazioni dei DM, dopo le presenze.
    $aTavolo = function ($tavolo) use ($daGiocare, $confermati, $session, $ospitiPresenti) {
        if ($daGiocare) {
            return $confermati->where('session_table_id', $tavolo->id)
                ->map(fn ($p) => ['nome' => $p->displayName(), 'pg' => $p->character?->name ?? $p->guest_character])
                ->values();
        }

        return $session->attendees->filter(fn ($u) => $u->pivot->session_table_id === $tavolo->id)
            ->map(fn ($u) => ['nome' => $u->name, 'pg' => $u->characters->firstWhere('id', $u->pivot->character_id)?->name])
            ->concat($ospitiPresenti->where('session_table_id', $tavolo->id)->map(fn ($o) => ['nome' => $o->guest_name, 'pg' => $o->guest_character]))
            ->values();
    };

    $senzaTavolo = $daGiocare ? $confermati->whereNull('session_table_id') : collect();
@endphp

<x-panel>
    <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
        <x-icona :is="Icon::Characters" class="h-5 w-5" /> I tavoli
    </h3>

    <div class="mt-3 space-y-4">
        @foreach ($session->tables as $tavolo)
            @php $giocatori = $aTavolo($tavolo); @endphp

            <section class="rounded-card border border-line p-3">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="font-semibold text-fg">{{ $tavolo->name }}</p>
                    <p class="text-xs text-muted">{{ $giocatori->count() }} / {{ $tavolo->seats }}</p>
                </div>

                @if ($tavolo->dms->isNotEmpty())
                    <p class="text-sm text-muted">{{ $tavolo->dms->count() === 1 ? 'Conduce' : 'Conducono' }} {{ $tavolo->dms->pluck('name')->join(', ', ' e ') }}</p>
                @endif

                @if ($giocatori->isNotEmpty())
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($giocatori as $g)
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-fg">{{ $g['nome'] }}</span>
                                @if (filled($g['pg']))
                                    <span class="text-xs text-muted">{{ $g['pg'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm italic text-muted">{{ $daGiocare ? 'Nessuno ancora a questo tavolo.' : 'Nessuna presenza segnata a questo tavolo.' }}</p>
                @endif

                @if ($tavolo->hasRecap())
                    <div class="mt-3 border-t border-line pt-3">
                        <p class="text-xs uppercase tracking-wide text-muted">Al {{ $tavolo->name }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-fg">{{ $tavolo->recap }}</p>
                        @if ($tavolo->recapWrittenBy)
                            <p class="mt-2 text-xs text-muted">Scritto da {{ $tavolo->recapWrittenBy->name }}, {{ $tavolo->recap_written_at?->translatedFormat('j F Y') }}</p>
                        @endif
                    </div>
                @endif

                @if ($scrive && ! $daGiocare)
                    <details class="mt-3 border-t border-line pt-3">
                        <summary class="cursor-pointer text-sm font-semibold text-fg">
                            {{ $tavolo->hasRecap() ? 'Correggi il resoconto del tavolo' : 'Scrivi il resoconto del tavolo' }}
                        </summary>
                        <p class="mt-1 text-xs text-muted">Facoltativo: quello che è successo solo qui. Il resoconto generale resta quello della sessione.</p>
                        <form method="POST" action="{{ route('sessions.tables.recap', [$session, $tavolo]) }}" class="mt-2">
                            @csrf
                            <textarea name="recap" rows="6" maxlength="20000" required class="{{ $campo }}">{{ $tavolo->recap }}</textarea>
                            <x-button variant="secondary" size="sm" class="mt-2">Salva</x-button>
                        </form>
                    </details>
                @endif
            </section>
        @endforeach
    </div>

    {{-- Ci si prenota alla sessione: il tavolo lo decidono i DM. --}}
    @if ($gestisce && $confermati->isNotEmpty())
        <form method="POST" action="{{ route('sessions.tables.assign', $session) }}" class="mt-4 border-t border-line pt-3">
            @csrf
            <p class="text-xs uppercase tracking-wide text-muted">Assegna i tavoli</p>
            <p class="mt-1 text-xs text-muted">
                @if ($senzaTavolo->isNotEmpty())
                    {{ $senzaTavolo->count() === 1 ? '1 confermato ancora senza tavolo.' : $senzaTavolo->count().' confermati ancora senza tavolo.' }}
                @else
                    Tutti i confermati hanno un tavolo.
                @endif
            </p>

            <div class="mt-2 space-y-2">
                @foreach ($confermati as $posto)
                    <label class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="text-fg">
                            {{ $posto->displayName() }}
                            @if ($posto->characterName())
                                <span class="text-muted">con {{ $posto->characterName() }}</span>
                            @endif
                        </span>
                        <select name="tavoli[{{ $posto->id }}]" aria-label="Tavolo di {{ $posto->displayName() }}"
                                class="max-w-full rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">
                            <option value="">Senza tavolo</option>
                            @foreach ($session->tables as $tavolo)
                                <option value="{{ $tavolo->id }}" @selected($posto->session_table_id === $tavolo->id)>{{ $tavolo->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>

            <x-button variant="secondary" size="sm" class="mt-3">Salva i tavoli</x-button>
        </form>
    @endif
</x-panel>
