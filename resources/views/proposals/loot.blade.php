@extends('layouts.app')
@section('title', 'Registra un bottino')

@use('App\Actions\Characters\ProposeChange')

@php
    $massimo = ProposeChange::LOOT_MAX_ITEMS;

    // Al ritorno con un errore restano aperte tutte le righe già compilate.
    $compilate = collect(old('items', []))
        ->filter(fn ($riga) => collect($riga)->filter(fn ($v) => filled($v))->isNotEmpty())
        ->keys()
        ->max();
    $aperte = max(3, $compilate === null ? 0 : $compilate + 1);

    $campo = 'w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted';
@endphp

@section('content')
<x-pagina larghezza="stretta" class="space-y-4">
    <x-panel>
        <h2 class="text-xl text-fg">Bottino di sessione</h2>
        <p class="mt-1 text-sm text-muted">
            Le monete si <strong>sommano</strong> a quelle che hai: se spendi qualcosa mentre la
            richiesta aspetta, la spesa non viene annullata.
        </p>
        <p class="mt-1 text-sm text-muted">
            Per ogni richiesta: monete per al massimo {{ \App\Domain\Dnd\Coins::formatValue(ProposeChange::LOOT_MAX_CP) }}
            e {{ $massimo }} oggetti.
        </p>
    </x-panel>

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <form method="POST" action="{{ route('proposals.loot', $character) }}" class="space-y-4">
        @csrf

        <x-panel title="Monete">
            <x-campo-monete name="coins" label="Quante monete, per tipo" />
        </x-panel>

        <x-panel title="Oggetti">
            <p class="mb-3 text-sm text-muted">
                Scrivi il nome e scegli fra i suggerimenti: catalogo, Emporio e oggetti già trovati da altri
                riempiono il resto da soli. Se non c'è, scrivilo tu: nome breve, descrizione nei dettagli,
                valore in mo (anche 0,5). Lascia in bianco le righe che non ti servono.
            </p>

            @include('partials.righe-oggetti', [
                'limite' => 'Hai raggiunto il limite di '.$massimo.' oggetti: registra il resto con una seconda richiesta.',
            ])

            <p class="mt-3 text-xs text-muted">
                Il tipo e un eventuale bonus magico li conferma il dungeon master quando approva.
            </p>
        </x-panel>

        @include('proposals.partials.sessione')

        <x-panel>
            <x-field name="note" label="Da dove arriva (facoltativo)" maxlength="255" />
        </x-panel>

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Registra il bottino</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</x-pagina>
@endsection
