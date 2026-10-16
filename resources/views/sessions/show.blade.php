@extends('layouts.app')
@section('title', $session->displayTitle())

@section('content')
@php
    use App\Enums\Icon;

    $campagna = $session->campaign;
    $scrive = auth()->user()->can('writeRecap', $session);
    $segna = auth()->user()->can('recordAttendance', $session);

    // Conserva l'origine nell'URL per mantenere coerenti il tasto indietro e la navigazione tra sessioni.
    // Se manca, una pagina aperta direttamente torna alla campagna.
    $da = request()->string('da')->toString() ?: null;

    $ritorno = match ($da) {
        'libro-mastro' => ['url' => route('ledger.index'), 'testo' => 'Torna al Libro Mastro'],
        'serate' => ['url' => route('sessions.index'), 'testo' => 'Torna alle sessioni'],
        'regia' => ['url' => route('dm.home', ['campagna' => $campagna->slug]), 'testo' => 'Torna all\'Area Master'],
        default => ['url' => route('campaigns.show', $campagna), 'testo' => 'Torna a '.$campagna->title],
    };

// Mostra la campagna come sopratitolo solo quando il link di ritorno non la nomina già.
    $frecciaNominaCampagna = ! in_array($da, ['libro-mastro', 'serate', 'regia'], true);
@endphp

<x-pagina class="space-y-6">

    <x-back dove="sopra" :href="$ritorno['url']">
        {{ $ritorno['testo'] }}
    </x-back>

    <div class="text-center">

        @unless ($frecciaNominaCampagna)
            <p class="mb-1 text-xs uppercase tracking-wide text-muted">
                <a href="{{ route('campaigns.show', $campagna) }}" class="transition hover:text-fg">{{ $campagna->title }}</a>
            </p>
        @endunless

        @if ($session->isDraft())
            <x-badge tone="outline" class="mb-2">Bozza: i giocatori non la vedono</x-badge>
        @endif

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

        {{-- Solo se c'è qualcuno oltre al titolare: altrimenti lo dice già la campagna. --}}
        @if ($session->dms->isNotEmpty())
            @php $conducono = $session->conductors(); @endphp
            <p class="mt-1 text-sm text-muted">{{ $conducono->count() === 1 ? 'Conduce' : 'Conducono' }} {{ $conducono->pluck('name')->join(', ', ' e ') }}</p>
        @endif
    </div>

    {{-- Due colonne fisse: a sinistra le persone (chi gioca, chi c'era, chiudere), a destra preparazione e racconto.
         Senza niente a destra, la colonna sola si centra sotto il titolo. --}}
    @php
        $destra = auth()->user()->isDm() || $session->quests->isNotEmpty() || $session->hasRecap() || ! $session->isUpcoming();
    @endphp

    <div @class(['grid gap-6 lg:items-start', 'lg:grid-cols-2' => $destra, 'mx-auto w-full max-w-xl' => ! $destra])>
        <div class="min-w-0 space-y-6">
        @if ($session->isUpcoming())
            @include('sessions.partials.prenotazioni')
        @endif

        {{-- Con i tavoli, chi c'era si legge tavolo per tavolo. --}}
        @if ($session->tables->isNotEmpty())
            @include('sessions.partials.tavoli')
        @endif

    {{-- Le presenze vengono mostrate solo dopo la sessione: una prenotazione non equivale a una presenza. --}}
        @if (! $session->isUpcoming() && $session->tables->isEmpty())
            <x-panel>
                <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                    <x-icona :is="Icon::Characters" class="h-5 w-5" /> Chi c'era
                </h3>

                @if ($session->attendees->isNotEmpty() || $ospitiPresenti->isNotEmpty())
                    <ul class="mt-3 space-y-1">
                        @foreach ($ospitiPresenti as $ospite)
                            <li class="text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-fg">{{ $ospite->guest_name }} <span class="text-xs text-muted">· ospite</span></span>
                                    @if (filled($ospite->guest_character))
                                        <span class="text-xs text-muted">{{ $ospite->guest_character }}</span>
                                    @endif
                                </div>
                                @can('manageGuests', $session)
                                    @include('sessions.partials.ospite-comandi', ['ospite' => $ospite])
                                @endcan
                            </li>
                        @endforeach

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
        @endif

        @if ($scrive || $segna)
            @include('sessions.partials.chiudi')
        @endif
        </div>

        @if ($destra)
            <div class="min-w-0 space-y-6">
            @if (auth()->user()->isDm())
                <x-panel>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
                            <x-icona :is="Icon::Characters" class="h-5 w-5" /> Gli eroi della sessione
                        </h3>

                        <a href="{{ route('encounters.index', ['campagna' => $campagna->slug, 'serata' => $session->id]) }}"
                           class="inline-flex items-center gap-2 rounded-full border border-line px-3 py-1.5
                                  text-sm font-semibold text-fg transition hover:border-active">
                            <x-icona :is="Icon::Sessions" class="h-4 w-4" />
                            Combattimenti
                        </a>
                    </div>

                    <div class="mt-3">
                        @include('dm.partials.eroi', ['eroi' => $eroi, 'vuoto' => 'Gli eroi compariranno qui quando qualcuno avrà il posto confermato.'])
                    </div>

                    <p class="mt-3 text-xs text-muted">
                        Tocca un eroe per la sua scheda: lì hai i comandi da DM (punti ferita, monete, «dichiara caduto»).
                    </p>

                    @if ($combattimenti->isNotEmpty())
                        <div class="mt-4 border-t border-line pt-3">
                            <p class="text-xs uppercase tracking-wide text-muted">Combattimenti di questa sessione</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($combattimenti as $scontro)
                                    <li class="flex flex-wrap items-baseline justify-between gap-2">
                                        <a href="{{ route('encounters.show', $scontro) }}" class="text-fg hover:underline">{{ $scontro->title }}</a>
                                        <span class="text-xs text-muted">
                                            {{ $scontro->status->label() }} · round {{ $scontro->round }}
                                            @if ($scontro->defeated()) · a terra: {{ implode(', ', $scontro->defeated()) }} @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 border-t border-line pt-3">
                        <livewire:session-prep :session="$session" />
                    </div>
                </x-panel>
            @endif

            @if ($session->quests->isNotEmpty())
                @include('sessions.partials.quest')
            @endif


            @if ($session->hasRecap())
                <x-panel>
                    <p class="text-xs uppercase tracking-wide text-muted">Com'è andata</p>
        {{-- Blade esegue l'escape del resoconto; `whitespace-pre-line` conserva gli a capo senza renderizzare HTML. --}}
                    <p class="mt-2 whitespace-pre-line text-sm text-fg">{{ $session->recap }}</p>

                    @if ($session->recapWrittenBy)
                        <p class="mt-3 border-t border-line pt-3 text-xs text-muted">
                            Scritto da {{ $session->recapWrittenBy->name }}@if ($session->recapBySubstitute() && $campagna->dm), in sostituzione di {{ $campagna->dm->name }}@endif,
                            {{ $session->recap_written_at?->translatedFormat('j F Y') }}
                        </p>
                    @endif

                    <x-reactions :for="$session" class="mt-4 border-t border-line pt-3" />
                </x-panel>
            @elseif (! $session->isUpcoming())
                <x-empty>Il resoconto non è ancora stato scritto.</x-empty>
            @endif

            @if ($diarioPg)
                <x-panel id="diario" class="scroll-mt-20">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-xs uppercase tracking-wide text-muted">Il tuo diario</p>
                        {{-- Solo per le note lunghe: le corte si leggono già tutte. --}}
                        @if (filled($diarioNota) && (mb_strlen($diarioNota) > 250 || substr_count($diarioNota, "\n") > 3))
                            <button type="button" data-espandi="diario-testo" data-testo-chiuso="Leggi tutta" data-testo-aperto="Mostra meno"
                                    class="text-xs font-semibold text-active hover:underline">Leggi tutta</button>
                        @endif
                    </div>

                    @if (filled($diarioNota))
                        <p id="diario-testo" class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-fg">{{ $diarioNota }}</p>
                    @else
                        <p class="mt-2 text-sm text-muted">Hai giocato questa sessione con {{ $diarioPg->name }}: scrivi cosa vuoi ricordarne. La leggono solo tu, i dungeon master e gli admin.</p>
                    @endif

                    <x-button type="button" variant="secondary" size="sm" class="mt-3" data-apri-dialogo="nota-{{ $session->id }}">
                        {{ filled($diarioNota) ? 'Modifica' : 'Scrivi nel diario' }}
                    </x-button>
                    @include('characters.partials.nota-dialogo', [
                        'character' => $diarioPg, 'sessione' => $session, 'nota' => $diarioNota, 'scrive' => true, 'daRegistro' => false,
                    ])
                </x-panel>
            @endif
            </div>
        @endif
    </div>

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
</x-pagina>

@include('partials.conferma')
@endsection
