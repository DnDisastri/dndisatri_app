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
                Il nome breve, la descrizione nei dettagli, il valore in mo (anche 0,5).
                Lascia in bianco le righe che non ti servono.
            </p>

            @error('items')
                <p class="mb-3 text-sm text-on-danger-soft">{{ $message }}</p>
            @enderror

            <div data-righe-bottino>
                @for ($i = 0; $i < $massimo; $i++)
                    <div data-riga-bottino @if ($i >= $aperte) hidden @endif
                         class="border-t border-line py-3 first:border-0 first:pt-0">
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                            <input type="text" name="items[{{ $i }}][name]" placeholder="Nome" maxlength="100"
                                   value="{{ old("items.$i.name") }}" aria-label="Nome dell'oggetto {{ $i + 1 }}"
                                   class="{{ $campo }} col-span-3">
                            <input type="number" name="items[{{ $i }}][qty]" placeholder="Q.tà" min="1" max="999"
                                   value="{{ old("items.$i.qty") }}" aria-label="Quantità"
                                   class="{{ $campo }}">
                            <input type="text" name="items[{{ $i }}][category]" placeholder="Categoria" maxlength="50"
                                   value="{{ old("items.$i.category") }}" aria-label="Categoria"
                                   class="{{ $campo }}">
                            <input type="number" name="items[{{ $i }}][value]" placeholder="Valore" min="0" step="0.01"
                                   value="{{ old("items.$i.value") }}" aria-label="Valore in mo"
                                   class="{{ $campo }}">
                            <textarea name="items[{{ $i }}][details]" rows="2" placeholder="Dettagli (facoltativo)" maxlength="1000"
                                      aria-label="Dettagli" class="{{ $campo }} col-span-3 sm:col-span-6">{{ old("items.$i.details") }}</textarea>
                        </div>

                        @foreach (['name', 'qty', 'category', 'value', 'details'] as $chiave)
                            @error("items.$i.$chiave")
                                <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                            @enderror
                        @endforeach
                    </div>
                @endfor
            </div>

            <div class="mt-3">
                <div data-aggiungi-oggetto @if ($aperte >= $massimo) hidden @endif>
                    <x-button type="button" variant="quiet" size="sm">Aggiungi un oggetto</x-button>
                </div>
                <p data-limite-oggetti @if ($aperte < $massimo) hidden @endif class="text-sm text-muted">
                    Hai raggiunto il limite di {{ $massimo }} oggetti: registra il resto con una seconda richiesta.
                </p>
            </div>
        </x-panel>

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
