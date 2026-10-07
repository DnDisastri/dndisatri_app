@extends('layouts.app')
@section('title', 'Area Master')

@section('content')

@php
    use App\Enums\Icon;

    $quando = $sessione?->played_at;

// Mostra la sessione di oggi, altrimenti la prossima; se non ce ne sono future usa l'ultima giocata.
    $statoSessione = match (true) {
        $sessione === null => null,
        $quando->isToday() => 'oggi',
        $quando->isFuture() => 'prossima',
        default => 'ultima',
    };
@endphp

<x-pagina class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide text-muted">Area Master</p>
            <h1 class="truncate text-2xl text-fg">
                {{ $corrente?->title ?? 'Nessuna campagna' }}
            </h1>
        </div>

        @if ($mie->isNotEmpty() || $altre->isNotEmpty())
            <details class="relative" data-tendina>
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-line
                                bg-surface px-3 py-2 text-sm font-semibold text-fg transition hover:border-active
                                [&::-webkit-details-marker]:hidden">
                    Cambia
                    <x-icona :is="Icon::Expand" class="h-4 w-4" />
                </summary>

                <nav class="absolute right-0 z-10 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-surface shadow-lg shadow-black/10">
                    @if ($mie->isNotEmpty())
                        <p class="border-b border-line px-4 py-2 text-xs uppercase tracking-wide text-muted">Le mie</p>
                        @foreach ($mie as $c)
                            <a href="{{ route('dm.home', ['campagna' => $c->slug]) }}"
                               @class([
                                   'block px-4 py-2.5 text-sm hover:bg-page',
                                   'font-semibold text-active' => $corrente && $c->is($corrente),
                                   'text-fg' => ! ($corrente && $c->is($corrente)),
                               ])>{{ $c->title }}</a>
                        @endforeach
                    @endif

                    @if ($altre->isNotEmpty())
                        <p class="border-b border-t border-line px-4 py-2 text-xs uppercase tracking-wide text-muted">
                            Degli altri · emergenza
                        </p>
                        @foreach ($altre as $c)
                            <a href="{{ route('dm.home', ['campagna' => $c->slug]) }}"
                               @class([
                                   'block px-4 py-2.5 text-sm hover:bg-page',
                                   'font-semibold text-active' => $corrente && $c->is($corrente),
                                   'text-fg' => ! ($corrente && $c->is($corrente)),
                               ])>
                                {{ $c->title }}
                                <span class="block text-xs text-muted">conduce {{ $c->dm?->name ?? 'Vuoto' }}</span>
                            </a>
                        @endforeach
                    @endif
                </nav>
            </details>
        @endif
    </div>

    @if ($sostituto)
        <x-note>
            Stai coprendo <strong>{{ $corrente->dm?->name ?? 'un collega' }}</strong> su
            «{{ $corrente->title }}». Puoi condurre e chiudere la sessione; calendario e campagna restano suoi.
        </x-note>
    @endif

    @if ($corrente === null)
        <x-empty>
            Non ci sono campagne attive da condurre. Quando ne apri una dal Pannello,
            comparirà qui.
        </x-empty>
    @else
        <div class="space-y-6 xl:grid xl:grid-cols-2 xl:items-start xl:gap-6 xl:space-y-0">
        <section class="space-y-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xs uppercase tracking-wide text-muted">
                    {{ $statoSessione === 'ultima' ? 'Ultima sessione' : 'Stasera' }}
                </h2>
                <a href="{{ route('sessions.index') }}" class="text-xs font-semibold text-active">Tutte le sessioni ›</a>
            </div>

            @if ($sessione === null)
                <x-empty>Nessuna sessione in programma per questa campagna.</x-empty>
            @else
                <x-panel class="ring-1 ring-active">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($statoSessione === 'oggi')
                                <x-badge tone="own">In corso · oggi</x-badge>
                            @elseif ($statoSessione === 'prossima')
                                <x-badge tone="accent">Prossima</x-badge>
                            @else
                                <x-badge tone="neutral">Giocata</x-badge>
                            @endif

                            <p class="mt-2 font-display text-lg leading-tight text-fg">
                                {{ $sessione->numberLabel() }}
                                @if (filled($sessione->title))
                                    <span class="block text-base text-muted">{{ $sessione->title }}</span>
                                @endif
                            </p>

                            <p class="mt-1 text-sm text-muted">
                                {{ $sessione->played_at->translatedFormat('l j F, H:i') }}
                                @if ($sessione->isUpcoming())
                                    · {{ $sessione->participantCount() }} / {{ $sessione->max_players }} prenotati
                                    @if ($sessione->bookings()->waiting()->exists()) · lista d'attesa @endif
                                    · {{ $sessione->isConfirmed() ? 'confermata' : 'da confermare' }}
                                @endif
                            </p>
                        </div>
                    </div>
{{-- `da=regia` conserva l'origine, così dalla sessione il DM può tornare all'Area Master. --}}
                    <a href="{{ route('sessions.show', ['session' => $sessione, 'da' => 'regia']) }}"
                       class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-active px-4 py-3
                              text-sm font-bold text-on-active transition hover:opacity-90">
                        Conduci la sessione
                        <x-icona :is="Icon::GoTo" class="h-4 w-4" />
                    </a>
                </x-panel>
            @endif
        </section>

            <section class="space-y-3">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-xs uppercase tracking-wide text-muted">Combattimenti</h2>
                    <a href="{{ route('encounters.index', ['campagna' => $corrente->slug]) }}" class="text-xs font-semibold text-active">Tutti e nuovo ›</a>
                </div>

                @forelse ($combattimenti as $scontro)
                    @include('dm.encounters.partials.riga', ['scontro' => $scontro])
                @empty
                    <a href="{{ route('encounters.index', ['campagna' => $corrente->slug]) }}"
                       class="flex items-center justify-center gap-2 rounded-card border border-dashed border-line px-4 py-4 text-sm font-semibold text-fg transition hover:border-active">
                        <x-icona :is="Icon::Sessions" class="h-4 w-4" /> Prepara un combattimento
                    </a>
                @endforelse
            </section>
        </div>

        <nav class="grid grid-cols-2 gap-3" aria-label="Strumenti del DM">
            <a href="{{ route('dm.npcs', ['campagna' => $corrente->slug]) }}"
               class="flex items-center justify-center gap-2 rounded-card border border-line bg-surface px-4 py-3 text-sm font-semibold text-fg transition hover:border-active">
                <x-icona :is="Icon::Characters" class="h-4 w-4" /> PNG
            </a>
            <a href="{{ route('dm.manual', ['campagna' => $corrente->slug]) }}"
               class="flex items-center justify-center gap-2 rounded-card border border-line bg-surface px-4 py-3 text-sm font-semibold text-fg transition hover:border-active">
                <x-icona :is="Icon::Manual" class="h-4 w-4" /> Manuale
            </a>
        </nav>

        <div class="space-y-6 xl:grid xl:grid-cols-2 xl:items-start xl:gap-6 xl:space-y-0">
            <section class="space-y-3">
                <h2 class="text-xs uppercase tracking-wide text-muted">Nota di passaggio</h2>

                <x-panel>
                    <form method="POST" action="{{ route('dm.handover', $corrente) }}" class="space-y-2">
                        @csrf
                        @method('PUT')
                        <label for="handover" class="sr-only">Nota di passaggio</label>
                        <textarea id="handover" name="handover_notes" rows="5" maxlength="5000"
                                  placeholder="Dove siamo rimasti, PNG in gioco, fili aperti. La leggono solo i DM."
                                  class="w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg">{{ old('handover_notes', $corrente->handover_notes) }}</textarea>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-muted">
                                @if ($corrente->handover_updated_at)
                                    Aggiornata da {{ $corrente->handoverUpdatedBy?->name ?? 'un DM' }}, {{ $corrente->handover_updated_at->diffForHumans() }}.
                                @else
                                    Ancora vuota.
                                @endif
                            </p>
                            <x-button size="sm" variant="secondary">Salva la nota</x-button>
                        </div>
                    </form>
                </x-panel>
            </section>

        <section class="space-y-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xs uppercase tracking-wide text-muted">{{ $eroiPrenotati ? 'I prenotati' : 'Chi ha giocato la campagna' }}</h2>
                <a href="{{ route('guild.index') }}" class="text-xs font-semibold text-active">Gilda ›</a>
            </div>

            @include('dm.partials.eroi', ['eroi' => $eroi])
        </section>
        </div>
    @endif
</x-pagina>
@endsection
