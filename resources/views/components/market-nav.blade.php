@props(['character' => null, 'characters' => null, 'esito' => null])

{{-- Le linguette stanno nella pagina (market/index). --}}
<div class="mb-5">
    @if ($character)
        <x-card padding="sm" class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm">
                {{-- La tendina solo con più personaggi: un DM può averne diversi. --}}
                @if ($characters && $characters->count() > 1)
                    <select wire:model.live="characterId"
                            class="rounded-md border border-line bg-page px-2 py-1 text-sm font-semibold text-fg">
                        @foreach ($characters as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                @else
                    <strong class="text-fg">{{ $character->name }}</strong>
                @endif
                <span class="block text-xs text-muted">stai comprando come</span>
            </div>

            <x-badge tone="accent" size="md">{{ number_format($character->gp, 0, ',', '.') }} mo</x-badge>
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
