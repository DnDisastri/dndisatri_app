<div>
    <x-panel title="Inventario">
        @php $borsa = $character->coins(); @endphp

        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <dl class="grid grid-cols-4 gap-2">
                @foreach (\App\Domain\Dnd\Coin::descending() as $moneta)
                    <div class="rounded-md border border-line bg-page px-2 py-1 text-center">
                        <dt class="text-xs text-muted" title="{{ $moneta->label() }}">{{ $moneta->abbreviation() }}</dt>
                        <dd class="font-bold text-fg">{{ number_format($borsa->get($moneta), 0, ',', '.') }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($canManage)
                <x-button type="button" variant="quiet" size="sm" wire:click="apriCambio">Cambia monete</x-button>
            @endif
        </div>

        <p class="mb-1 text-xs text-muted">Valore: <x-monete :valore="$borsa->value()" /></p>

        <p class="mb-3 text-xs text-muted">
            In sintonia: {{ $character->attunedItems()->count() }} / {{ App\Models\Character::ATTUNEMENT_LIMIT }}
        </p>

        @error('inventario')
            <p class="mb-2 rounded border border-line bg-danger-soft px-3 py-2 text-sm text-on-danger-soft">
                {{ $message }}
            </p>
        @enderror

        @forelse ($items as $item)
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-t border-line py-1.5 text-sm first:border-0">
                <span>
                    {{ $item->name }}
                    @if ($item->qty > 1)
                        <span class="text-muted">×{{ $item->qty }}</span>
                    @endif

                    @if ($item->isEquipped())
                        <span class="ml-1 rounded bg-accent-soft px-1.5 py-0.5 text-xs text-primary">
                            {{ $item->equipped_slot->label() }}
                        </span>
                    @endif

                    @if ($item->attuned)
                        <span class="ml-1 rounded bg-accent-soft px-1.5 py-0.5 text-xs text-on-accent-soft">
                            in sintonia
                        </span>
                    @endif

                    {{-- Più marcata delle altre: è l'unica cosa dello zaino che vedono gli altri. --}}
                    @if ($item->tradeable)
                        <span class="ml-1 rounded bg-primary px-1.5 py-0.5 text-xs text-on-primary">
                            in vetrina
                        </span>
                    @endif
                </span>

                <span class="flex items-center gap-2">
                    @if ($item->value)
                        <span class="text-muted">{{ $item->totalValue() }} mo</span>
                    @endif

                    @if ($canManage)
                        {{-- Indossare vale per armi, armature e scudi; la
                             sintonia per quello che porta un effetto. Sono due
                             cose diverse e i pulsanti restano distinti. --}}
                        @if ($item->isEquipped())
                            <button type="button" wire:click="unequip({{ $item->id }})"
                                    class="rounded border border-line px-2 py-0.5 text-xs hover:bg-page">
                                Riponi
                            </button>
                        @elseif (App\Enums\EquipmentSlot::Armor->accepts($item->name)
                                 || App\Enums\EquipmentSlot::Shield->accepts($item->name)
                                 || App\Enums\EquipmentSlot::Weapon->accepts($item->name))
                            <button type="button" wire:click="equip({{ $item->id }})"
                                    class="rounded border border-line px-2 py-0.5 text-xs hover:bg-page">
                                Indossa
                            </button>
                        @endif

                        @if ($magicItemIds->contains($item->id))
                            @if ($item->attuned)
                                <button type="button" wire:click="release({{ $item->id }})"
                                        class="rounded border border-line/40 px-2 py-0.5 text-xs text-on-accent-soft hover:bg-page">
                                    Togli sintonia
                                </button>
                            @else
                                <button type="button" wire:click="attune({{ $item->id }})"
                                        class="rounded border border-line/40 px-2 py-0.5 text-xs text-on-accent-soft hover:bg-page">
                                    Sintonizza
                                </button>
                            @endif
                        @endif
                    @endif

                    {{-- Sta fuori da `$canManage` perché è un permesso suo: gli
                         altri comandi li usa anche chi conduce, questo no. --}}
                    @if ($canShowcase)
                        <button type="button" wire:click="toggleTradeable({{ $item->id }})"
                                title="{{ $item->tradeable
                                    ? 'Togli dalla vetrina: gli altri non lo vedranno più'
                                    : 'Mettilo in vetrina: gli altri potranno chiedertelo in scambio' }}"
                                @class([
                                    'rounded border px-2 py-0.5 text-xs hover:bg-page',
                                    'border-primary text-primary' => $item->tradeable,
                                    'border-line/40 text-muted' => ! $item->tradeable,
                                ])>
                            {{ $item->tradeable ? 'Ritira' : 'Scambierei' }}
                        </button>
                    @endif
                </span>
            </div>
        @empty
            <p class="text-sm text-muted">Lo zaino è vuoto.</p>
        @endforelse
    </x-panel>

    @if ($modaleCambio)
        <x-modal title="Cambia monete" close="chiudiCambio">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">Solo cambi esatti: 1 mp = 10 mo = 100 ma = 1.000 mr.</p>

                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="mb-1 block text-muted">Da</span>
                        <select wire:model.live="cambioDa" class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                            @foreach (\App\Domain\Dnd\Coin::descending() as $moneta)
                                <option value="{{ $moneta->value }}">{{ $moneta->label() }} ({{ $moneta->abbreviation() }})</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-muted">In</span>
                        <select wire:model.live="cambioA" class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                            @foreach (\App\Domain\Dnd\Coin::descending() as $moneta)
                                <option value="{{ $moneta->value }}">{{ $moneta->label() }} ({{ $moneta->abbreviation() }})</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-muted">Quante ne cambi</span>
                    <input type="number" min="1" inputmode="numeric" wire:model.live.debounce.300ms="cambioQuante"
                           class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg">
                </label>
                @error('cambioA') <p class="text-on-danger-soft">{{ $message }}</p> @enderror
                @error('cambioQuante') <p class="text-on-danger-soft">{{ $message }}</p> @enderror

                <p class="rounded-md bg-page px-3 py-2 text-muted">
                    @if ($anteprima)
                        Dopo il cambio: <span class="font-semibold text-fg">{{ $anteprima->format() }}</span>
                    @else
                        Scegli due monete e una quantità che dia un cambio esatto.
                    @endif
                </p>

                <x-button full type="button" wire:click="cambia" :disabled="$anteprima === null">Cambia</x-button>
            </div>
        </x-modal>
    @endif
</div>
