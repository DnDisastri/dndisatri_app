@extends('layouts.app')
@section('title', 'Oggetto magico')

@php use App\Domain\Dnd\Ability; @endphp

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">
    <x-panel>
        <h2 class="text-xl text-fg">Oggetto magico</h2>
        <p class="mt-1 text-sm text-muted">
            Un oggetto che altera una caratteristica. I punteggi base non vengono toccati:
            togliendo l'oggetto, tutto torna com'era.
        </p>
    </x-panel>

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <form method="POST" action="{{ route('proposals.item-effect', $character) }}" class="space-y-4">
        @csrf

        <x-panel>
            <div class="space-y-4">
                <x-field name="name" label="Nome dell'oggetto" required
                         placeholder="Cintura di Forza del Gigante" />

                <div>
                    <label for="ability" class="mb-1 block text-sm font-medium text-fg">Caratteristica</label>
                    <select id="ability" name="ability"
                            class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-fg focus:border-active focus:outline-none">
                        @foreach (Ability::cases() as $ability)
                            <option value="{{ $ability->value }}" @selected(old('ability') === $ability->value)>
                                {{ $ability->fullName() }} (adesso {{ $character->getAttribute($ability->value) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-fg">Come agisce</label>
                    <label class="mb-1 flex items-start gap-2 text-sm">
                        <input type="radio" name="mode" value="set" class="mt-1 accent-active" @checked(old('mode', 'set') === 'set')>
                        <span>
                            <strong>Porta il punteggio a</strong> un valore fisso:
                            <span class="text-muted">vale solo se è un miglioramento</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="radio" name="mode" value="bonus" class="mt-1 accent-active" @checked(old('mode') === 'bonus')>
                        <span>
                            <strong>Somma</strong> al punteggio:
                            <span class="text-muted">accetta anche valori negativi</span>
                        </span>
                    </label>
                    @error('mode')
                        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                    @enderror
                </div>

                <x-field name="value" label="Valore" type="number" required />
            </div>
        </x-panel>

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Manda la richiesta</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</div>
@endsection
