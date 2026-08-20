@extends('layouts.app')
@section('title', $session->displayTitle())

@section('content')
@php
    use App\Enums\Icon;

    $campagna = $session->campaign;
    $scrive = auth()->user()->can('writeRecap', $session);
    $segna = auth()->user()->can('recordAttendance', $session);

    // Conserva l'origine nell'URL per mantenere coerenti il tasto indietro e la navigazione tra serate.
    // Se manca, una pagina aperta direttamente torna alla campagna.
    $da = request()->string('da')->toString() ?: null;

    $ritorno = match ($da) {
        'libro-mastro' => ['url' => route('ledger.index'), 'testo' => 'Torna al Libro Mastro'],
        'serate' => ['url' => route('sessions.index'), 'testo' => 'Torna alle serate'],
        'regia' => ['url' => route('dm.home', ['campagna' => $campagna->slug]), 'testo' => 'Torna alla Regia'],
        default => ['url' => route('campaigns.show', $campagna), 'testo' => 'Torna a '.$campagna->title],
    };

// Mostra la campagna come sopratitolo solo quando il link di ritorno non la nomina già.
    $frecciaNominaCampagna = ! in_array($da, ['libro-mastro', 'serate', 'regia'], true);
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-6">

    <x-back dove="sopra" :href="$ritorno['url']">
        {{ $ritorno['testo'] }}
    </x-back>

    <div class="text-center">

        @unless ($frecciaNominaCampagna)
            <p class="mb-1 text-xs uppercase tracking-wide text-muted">
                <a href="{{ route('campaigns.show', $campagna) }}" class="transition hover:text-fg">{{ $campagna->title }}</a>
            </p>
        @endunless

        <h2 class="text-2xl leading-tight text-fg">
            <span class="block">{{ $session->numberLabel() }}</span>
            @if (filled($session->title))
                <span class="block">{{ $session->title }}</span>
            @endif
        </h2>

        <p class="mt-1 flex flex-wrap items-center justify-center gap-2 text-sm text-muted">
            <span>{{ $session->played_at->translatedFormat('l j F Y, H:i') }}</span>

            @if ($session->isUpcoming())
                <span>·</span>
                <x-badge tone="accent">Da giocare</x-badge>
            @endif
        </p>
    </div>

    @if ($session->hasRecap())
        <x-panel>
            <p class="text-xs uppercase tracking-wide text-muted">Com'è andata</p>
{{-- Blade esegue l'escape del resoconto; `whitespace-pre-line` conserva gli a capo senza renderizzare HTML. --}}
            <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ $session->recap }}</p>

            @if ($session->recapWrittenBy)
                <p class="mt-3 border-t border-line pt-3 text-xs text-muted">
                    Scritto da {{ $session->recapWrittenBy->name }},
                    {{ $session->recap_written_at?->translatedFormat('j F Y') }}
                </p>
            @endif

            <x-reactions :for="$session" class="mt-4 border-t border-line pt-3" />
        </x-panel>
    @elseif (! $session->isUpcoming())
        <x-empty>Il resoconto non è ancora stato scritto.</x-empty>
    @endif
{{-- Le presenze vengono mostrate solo dopo la serata: una prenotazione alla quest non equivale a una presenza. --}}
    @unless ($session->isUpcoming())
        <x-panel>
            <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                <x-icona :is="Icon::Characters" class="h-5 w-5" /> Chi c'era
            </h3>

            @if ($session->attendees->isNotEmpty())
                <ul class="mt-3 space-y-1">
                    @foreach ($session->attendees as $presente)
                        @php $personaggio = $presente->characters->firstWhere('id', $presente->pivot->character_id); @endphp

                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-fg">{{ $presente->name }}</span>

                            @if ($personaggio)
                                <a href="{{ route('characters.show', $personaggio) }}"
                                   class="text-xs text-muted hover:underline">{{ $personaggio->name }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm italic text-muted">Le presenze non sono ancora state segnate.</p>
            @endif
        </x-panel>
    @endunless

    @if (auth()->user()->isDm())
        <x-panel>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                    <x-icona :is="Icon::Characters" class="h-5 w-5" /> Il tavolo
                </h3>

                <a href="{{ route('dm.prepare', $session) }}"
                   class="inline-flex items-center gap-2 rounded-full border border-line px-3 py-1.5
                          text-sm font-semibold text-fg transition hover:border-active">
                    <x-icona :is="Icon::Sessions" class="h-4 w-4" />
                    Prepara la serata
                </a>
            </div>

            <div class="mt-3">
                @include('dm.partials.tavolo', ['tavolo' => $tavolo])
            </div>

            <p class="mt-3 text-xs text-muted">
                Tocca un eroe per la sua scheda: lì hai i comandi da DM — punti ferita, oro, «dichiara caduto».
            </p>
        </x-panel>
    @endif

    @if ($scrive || $segna)

        {{-- Usa `ring` per evidenziare il pannello senza introdurre una seconda classe `border-*` concorrente. --}}
        <x-panel class="ring-1 ring-active">
            <h3 class="text-lg font-semibold text-fg">Conduci tu</h3>

            @can('writeRecap', $session)
                <form method="POST" action="{{ route('sessions.recap', $session) }}" class="mt-3">
                    @csrf

                    <label for="recap" class="text-xs uppercase tracking-wide text-muted">
                        {{ $session->hasRecap() ? 'Correggi il resoconto' : 'Scrivi il resoconto' }}
                    </label>

                    <textarea name="recap" id="recap" rows="10" maxlength="20000"
                              class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg"
                              placeholder="Cosa è successo, chi ha fatto cosa, com'è finita.">{{ old('recap', $session->recap) }}</textarea>

                    @error('recap')
                        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                    @enderror

                    <x-button class="mt-2">Salva il resoconto</x-button>
                </form>
            @endcan

            @can('recordAttendance', $session)
                <form method="POST" action="{{ route('sessions.attendance', $session) }}"
                      class="mt-4 border-t border-line pt-3">
                    @csrf

                    <p class="text-xs uppercase tracking-wide text-muted">Chi c'era</p>
                    <p class="mt-1 text-xs text-muted">
                        Chi si è presentato, non chi si era prenotato: sono due cose diverse.
                        Il personaggio si può lasciare vuoto.
                    </p>

                    <div class="mt-3 space-y-2">
                        @foreach ($candidates as $candidato)
                            @php
                                $presente = $session->attendees->firstWhere('id', $candidato->id);
                                $scelto = $presente?->pivot->character_id;
                            @endphp

                            <x-inset padding="sm" class="flex flex-wrap items-center justify-between gap-2">
                                <label class="flex items-center gap-2 text-sm text-fg">
                                    <input type="checkbox" name="presenti[]" value="{{ $candidato->id }}"
                                           @checked($presente)
                                           class="rounded border-line accent-[var(--ui-active)]">
                                    {{ $candidato->name }}
                                </label>
{{-- Offre solo i personaggi del partecipante; la stessa regola viene comunque validata lato server. --}}
                                @if ($candidato->characters->isNotEmpty())
                                    <select name="personaggi[{{ $candidato->id }}]"
                                            class="rounded-md border border-line bg-surface px-2 py-1 text-sm text-fg">
                                        <option value="">— senza personaggio —</option>
                                        @foreach ($candidato->characters as $personaggio)
                                            <option value="{{ $personaggio->id }}" @selected($scelto === $personaggio->id)>
                                                {{ $personaggio->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </x-inset>
                        @endforeach
                    </div>

                    <p class="mt-2 text-xs text-muted">
                        L'elenco si sostituisce: quello che salvi è la lista definitiva,
                        correzioni comprese.
                    </p>

                    <x-button variant="secondary" class="mt-2">Salva le presenze</x-button>
                </form>
            @endcan
        </x-panel>
    @endif

    @if ($precedente || $prossima)
        <nav class="mt-8 flex items-stretch justify-between gap-3 border-t border-line pt-4 text-sm">
            @if ($precedente)
                <a href="{{ route('sessions.show', array_filter(['session' => $precedente, 'da' => $da])) }}"
                   class="group inline-flex items-center gap-2 text-muted transition hover:text-fg">
                    <x-icona :is="Icon::Back" class="h-4 w-4 shrink-0" />

                    <span class="leading-tight">
                        <span class="block text-xs uppercase tracking-wide">Precedente</span>
                        <span class="block">{{ $precedente->numberLabel() }}</span>
                        @if (filled($precedente->title))
                            <span class="block">{{ $precedente->title }}</span>
                        @endif
                    </span>
                </a>
            @else
                <span></span>
            @endif

            @if ($prossima)
                <a href="{{ route('sessions.show', array_filter(['session' => $prossima, 'da' => $da])) }}"
                   class="group inline-flex items-center gap-2 text-right text-muted transition hover:text-fg">
                    <span class="leading-tight">
                        <span class="block text-xs uppercase tracking-wide">Prossima</span>
                        <span class="block">{{ $prossima->numberLabel() }}</span>
                        @if (filled($prossima->title))
                            <span class="block">{{ $prossima->title }}</span>
                        @endif
                    </span>
                    <x-icona :is="Icon::GoTo" class="h-4 w-4 shrink-0" />
                </a>
            @else
                <span></span>
            @endif
        </nav>
    @endif
</div>
@endsection
