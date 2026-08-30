@extends('layouts.app')
@section('title', 'Registro di '.$character->name)
{{-- Il registro è append-only: `gp_after` conserva il saldo dopo ogni movimento.
     Gli annullamenti aggiungono un nuovo movimento senza modificare lo storico. --}}
@section('content')
<div class="mx-auto max-w-3xl px-4 py-6">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Ledger" class="h-7 w-7" /> Registro
    </h2>

    @if ($filtrabile && $tutti)
        <p class="mb-6 text-sm text-muted">
            Ogni movimento di tutti i personaggi: bottini, acquisti, vendite,
            scambi e l'oro assegnato dai dungeon master. È da qui che si capisce
            dove è finito qualcosa.
        </p>
    @else
        <p class="mb-6 text-sm text-muted">
            Ogni movimento di {{ $character->name }}: bottini, acquisti, vendite,
            scambi e l'oro assegnato dai dungeon master.
        </p>
    @endif

    @if ($filtrabile)
        @php
            // Mantiene ambito e filtri nell'URL, così una vista filtrata resta condivisibile.
            $base = array_filter(
                ['tipo' => $tipo?->value, 'periodo' => $giorni ?: null],
                fn ($v) => $v !== null && $v !== '',
            );

            $ambito = $tutti
                ? ['character' => $character, 'pg' => 'tutti']
                : ['character' => $character];

            $conFiltri = fn (array $extra = []) => route('characters.ledger', array_filter(
                array_merge($ambito, ['tipo' => $tipo?->value, 'periodo' => $giorni ?: null], $extra),
                fn ($v) => $v !== null && $v !== '',
            ));

            $periodi = [7 => 'Ultima settimana', 30 => 'Ultimo mese', 90 => 'Ultimi tre mesi'];

            $stileSelect = 'rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg '
                .'focus:border-active focus:outline-none';
        @endphp

        <div class="mb-6 flex flex-wrap gap-2">
            <select onchange="location.href=this.value" aria-label="Personaggio" class="{{ $stileSelect }}">
                <option value="{{ route('characters.ledger', ['character' => $character, 'pg' => 'tutti'] + $base) }}"
                        @selected($tutti)>Tutti i personaggi</option>
                @foreach ($personaggi as $p)
                    <option value="{{ route('characters.ledger', ['character' => $p] + $base) }}"
                            @selected(! $tutti && $p->is($character))>{{ $p->name }}</option>
                @endforeach
            </select>

            <select onchange="location.href=this.value" aria-label="Tipo di movimento" class="{{ $stileSelect }}">
                <option value="{{ $conFiltri(['tipo' => null]) }}" @selected($tipo === null)>Tutti i tipi</option>
                @foreach ($azioni as $azione)
                    <option value="{{ $conFiltri(['tipo' => $azione->value]) }}"
                            @selected($tipo === $azione)>{{ $azione->label() }}</option>
                @endforeach
            </select>

            <select onchange="location.href=this.value" aria-label="Periodo" class="{{ $stileSelect }}">
                <option value="{{ $conFiltri(['periodo' => null]) }}" @selected($giorni === 0)>Da sempre</option>
                @foreach ($periodi as $g => $etichetta)
                    <option value="{{ $conFiltri(['periodo' => $g]) }}"
                            @selected($giorni === $g)>{{ $etichetta }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @unless ($filtrabile && $tutti)
        <x-panel class="mb-6 flex items-center justify-between gap-3">
            <span class="text-sm uppercase tracking-wide text-muted">Oro in tasca</span>
            <span class="flex items-center gap-2 text-2xl font-bold text-fg">
                <x-icona :is="\App\Enums\Icon::Gold" class="h-6 w-6" /> {{ $character->gp }}
            </span>
        </x-panel>
    @endunless

    <div class="space-y-2">
        @forelse ($entries as $entry)
            @php $proprietario = ($tutti ?? false) ? $entry->character?->user_id : $character->user_id; @endphp
            <x-card padding="sm">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-semibold text-fg">
                        {{ $entry->action->label() }}

                        @if (($tutti ?? false) && $entry->character)
                            <span class="font-normal text-muted">· {{ $entry->character->name }}</span>
                        @endif
                    </span>

                    @if ($entry->gp_delta !== 0)
                        <span @class([
                            'font-bold',
                            'text-primary' => $entry->gp_delta > 0,
                            'text-on-danger-soft' => $entry->gp_delta < 0,
                        ])>
                            {{ $entry->gp_delta > 0 ? '+' : '−' }}{{ abs($entry->gp_delta) }} mo
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-fg">{{ $entry->message }}</p>

                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                    <span>{{ $entry->created_at->translatedFormat('j F Y, H:i') }}</span>

                    @if ($entry->actor && $entry->actor_id !== $proprietario)
                        <span>· {{ $entry->actor->name }}</span>
                    @endif

                    @if ($entry->gp_after !== null)
                        <span>· saldo dopo: {{ $entry->gp_after }} mo</span>
                    @endif
                </p>

                @if ($entry->reversed_at)
                    <x-badge tone="danger" class="mt-2">
                        Annullato il {{ $entry->reversed_at->translatedFormat('j F Y') }}
                    </x-badge>
                @endif
            </x-card>
        @empty
            <x-empty size="lg">
                @if ($filtrabile && ($tutti || $tipo !== null || $giorni > 0))
                    Nessun movimento con questi filtri. Allarga il tipo o il
                    periodo, o torna a tutti i personaggi.
                @else
                    Nessun movimento. Il registro si riempie da solo: al primo
                    acquisto, al primo bottino, al primo oro assegnato.
                @endif
            </x-empty>
        @endforelse
    </div>

    <x-back :href="route('characters.show', $character)">Torna alla scheda</x-back>
</div>
@endsection
