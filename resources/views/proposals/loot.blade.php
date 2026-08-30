@extends('layouts.app')
@section('title', 'Registra un bottino')

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">
    <x-panel>
        <h2 class="text-xl text-fg">Bottino di sessione</h2>
        <p class="mt-1 text-sm text-muted">
            L'oro si <strong>somma</strong> a quello che hai: se spendi qualcosa mentre la
            richiesta aspetta, la spesa non viene annullata.
        </p>
    </x-panel>

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <form method="POST" action="{{ route('proposals.loot', $character) }}" class="space-y-4">
        @csrf

        <x-panel title="Oro">
            <x-field name="gp" label="Monete d'oro" type="number" min="0" value="0" />
        </x-panel>

        <x-panel title="Oggetti">
            <p class="mb-3 text-sm text-muted">Lascia in bianco le righe che non ti servono.</p>

            @for ($i = 0; $i < 4; $i++)
                <div class="mb-2 grid grid-cols-6 gap-2">
                    <input type="text" name="items[{{ $i }}][name]" placeholder="Nome"
                           value="{{ old("items.$i.name") }}"
                           class="col-span-3 rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">
                    <input type="number" name="items[{{ $i }}][qty]" placeholder="Q.tà" min="1"
                           value="{{ old("items.$i.qty") }}"
                           class="rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">
                    <input type="text" name="items[{{ $i }}][category]" placeholder="Categoria"
                           value="{{ old("items.$i.category") }}"
                           class="rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">
                    <input type="number" name="items[{{ $i }}][value]" placeholder="Valore" min="0"
                           value="{{ old("items.$i.value") }}"
                           class="rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">
                </div>
            @endfor
        </x-panel>

        <x-panel>
            <x-field name="note" label="Da dove arriva (facoltativo)" />
        </x-panel>

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Registra il bottino</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</div>
@endsection
