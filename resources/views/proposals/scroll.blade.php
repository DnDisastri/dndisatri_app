@extends('layouts.app')
@section('title', 'Copia da una pergamena')

@use('App\Actions\Characters\ProposeChange')

@php $campo = 'w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg focus:border-active focus:outline-none'; @endphp

@section('content')
<x-pagina larghezza="stretta" class="space-y-4">
    <x-panel>
        <h2 class="text-xl text-fg">Copia un incantesimo nel libro</h2>
        <p class="mt-1 text-sm text-muted">
            Costa {{ \App\Domain\Dnd\Coins::formatValue(ProposeChange::scrollCost(1)) }} per livello dell'incantesimo{{ $consuma ? ', e la pergamena si consuma' : '' }}.
            Le monete si pagano quando il dungeon master approva.
        </p>
        <p class="mt-1 text-sm text-muted">In borsa hai {{ \App\Domain\Dnd\Coins::formatValue($character->purseValue()) }}.</p>
    </x-panel>

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <form method="POST" action="{{ route('proposals.scroll', $character) }}" class="space-y-4">
        @csrf

        <x-panel title="Incantesimo">
            @if ($copiabili->isEmpty())
                <x-empty>Non c'è niente da copiare: conosci già tutti quelli alla tua portata.</x-empty>
            @else
                <select name="spell" aria-label="Incantesimo da copiare" class="{{ $campo }}">
                    <option value="">Scegli l'incantesimo</option>
                    @foreach ($copiabili as $livello => $nomi)
                        <optgroup label="Livello {{ $livello }}, {{ \App\Domain\Dnd\Coins::formatValue(ProposeChange::scrollCost($livello)) }}">
                            @foreach ($nomi as $nome)
                                <option value="{{ $nome }}" @selected(old('spell') === $nome)>{{ $nome }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('spell') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
            @endif
        </x-panel>

        @if ($consuma)
            <x-panel title="La pergamena">
                @if ($oggetti->isEmpty())
                    <p class="text-sm text-muted">
                        Lo zaino è vuoto: registra prima la pergamena con un bottino.
                    </p>
                @else
                    <p class="mb-2 text-sm text-muted">Scegli dallo zaino quella da cui copi: sparisce quando il dungeon master approva.</p>
                    <select name="item_id" aria-label="Pergamena" class="{{ $campo }}">
                        @foreach ($oggetti as $oggetto)
                            <option value="{{ $oggetto->id }}" @selected((string) old('item_id') === (string) $oggetto->id)>
                                {{ $oggetto->name }}{{ $oggetto->qty > 1 ? ' ×'.$oggetto->qty : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </x-panel>
        @endif

        @include('proposals.partials.sessione')

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1" :disabled="$copiabili->isEmpty() || ($consuma && $oggetti->isEmpty())">Chiedi la copia</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</x-pagina>
@endsection
