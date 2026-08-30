<div>
    <x-panel title="Inventario">
        <p class="mb-1 text-lg">
            <span class="font-bold text-fg">{{ number_format($character->gp, 0, ',', '.') }}</span>
            <span class="text-sm text-muted">monete d'oro</span>
        </p>

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

                    {{-- L'unica cosa di questo zaino che vedono gli altri. Il
                         segno è più marcato degli altri due apposta: le altre
                         due pillole dicono come tieni la tua roba, questa dice
                         che qualcun altro la sta guardando. --}}
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
</div>
