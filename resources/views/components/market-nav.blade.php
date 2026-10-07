@props(['character' => null, 'characters' => null, 'esito' => null])

{{-- Le linguette stanno nella pagina (market/index). --}}
<div class="mb-5">
    @if ($character)
        {{-- Con un nome lungo va a capo il nome: le monete restano su una riga. --}}
        <x-card padding="sm" class="flex items-center justify-between gap-2">
            <div class="min-w-0 flex-1 text-sm">
                <span class="block text-xs text-muted">stai comprando come</span>
                {{-- La tendina solo con più personaggi: un DM può averne diversi. --}}
                @if ($characters && $characters->count() > 1)
                    <select wire:model.live="characterId"
                            class="w-full max-w-full rounded-md border border-line bg-page px-2 py-1 text-sm font-semibold text-fg">
                        @foreach ($characters as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                @else
                    <strong class="block break-words text-fg">{{ $character->name }}</strong>
                @endif
            </div>

            <x-borsa :borsa="$character->coins()" compatta class="shrink-0" />
        </x-card>
    @else
        <x-note>Serve un personaggio in salute per vendere o comprare.</x-note>
    @endif

    @if ($esito)
        <x-note class="mt-3">{{ $esito }}</x-note>
    @endif

    @error('mercato')
        <x-note tone="danger" class="mt-3">{{ $message }}</x-note>
    @enderror

    @error('scambio')
        <x-note tone="danger" class="mt-3">{{ $message }}</x-note>
    @enderror
</div>
