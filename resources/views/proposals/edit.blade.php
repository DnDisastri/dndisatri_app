@extends('layouts.app')
@section('title', 'Proponi modifiche')

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">
    <x-panel>
        <h2 class="text-xl text-fg">Proponi modifiche</h2>
        <p class="mt-1 text-sm text-muted">
            La scheda non cambia subito: la proposta va in bacheca e la esamina un dungeon
            master. Verranno mandate solo le cose che tocchi davvero.
        </p>
    </x-panel>

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <form method="POST" action="{{ route('proposals.edit', $character) }}"
          enctype="multipart/form-data" class="space-y-4">
        @csrf

        <x-panel>
            <div class="space-y-4">
                <x-field name="name" label="Nome" :value="$character->name" required />

                <div>
                    <label for="photo" class="mb-1 block text-sm font-medium text-fg">Foto</label>

                    @if ($character->photoUrl())
                        <img src="{{ $character->photoUrl() }}" alt="{{ $character->name }}"
                             class="mb-2 h-24 w-24 rounded-lg object-cover">
                    @endif

                    <p class="mb-1 text-xs text-muted">
                        Un ritratto del personaggio. Anche questa la vede un dungeon master
                        prima che compaia in Gilda.
                    </p>
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                           class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg
                                  file:mr-3 file:rounded file:border-0 file:bg-surface file:px-3 file:py-1
                                  file:text-fg">
                    @error('photo')
                        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                    @enderror
                </div>
                <x-field name="background" label="Background" :value="$character->background" />

                <div>
                    <label for="story" class="mb-1 block text-sm font-medium text-fg">Storia</label>
                    <p class="mb-1 text-xs text-muted">
                        Chi è il tuo personaggio, in breve. La leggono gli altri giocatori dalla
                        Gilda: è l'unica parte della scheda pensata per essere letta da fuori.
                    </p>
                    <textarea id="story" name="story" rows="4" maxlength="2000"
                              class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-fg focus:border-active focus:outline-none">{{ old('story', $character->story) }}</textarea>
                    @error('story')
                        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                    @enderror
                </div>

                @foreach ([
                    'species_traits' => 'Tratti di specie',
                    'class_features' => 'Privilegi di classe',
                    'subclass_features' => 'Privilegi di sottoclasse',
                    'notes' => 'Note',
                ] as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="mb-1 block text-sm font-medium text-fg">{{ $label }}</label>
                        <textarea id="{{ $field }}" name="{{ $field }}" rows="4"
                                  class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-fg focus:border-active focus:outline-none">{{ old($field, $character->getAttribute($field)) }}</textarea>
                        @error($field)
                            <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </x-panel>

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Manda la proposta</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</div>
@endsection
