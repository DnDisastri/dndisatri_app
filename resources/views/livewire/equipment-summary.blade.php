<div class="space-y-4">
    <x-panel title="Equipaggiamento">
        <ul class="space-y-1">
            @foreach (App\Enums\EquipmentSlot::cases() as $slot)
                @php $indossato = $character->equipped($slot); @endphp

                <li class="flex flex-wrap items-baseline justify-between gap-2
                           rounded px-2 py-1.5 text-sm {{ $indossato ? 'bg-page' : '' }}">
                    <span class="text-muted">{{ $slot->label() }}</span>

                    @if ($indossato)
                        <span class="flex items-baseline gap-2">
                            <span class="font-medium text-fg">{{ $indossato->name }}</span>
                            <span class="rounded bg-accent-soft px-1.5 py-0.5 text-xs text-primary">
                                {{ $slot->equippedLabel() }}
                            </span>
                        </span>
                    @else
                        <span class="text-muted">{{ $slot->emptyLabel() }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

        <p class="mt-2 border-t border-line pt-2 text-xs text-muted">
            Si cambia dai pulsanti dell'inventario.
        </p>
    </x-panel>

    @if ($character->itemEffects->isNotEmpty())
        @php $active = $character->activeEffects()->keyBy('id'); @endphp

        <x-panel title="Oggetti magici">
            <p class="mb-2 text-xs text-muted">
                In sintonia: {{ $character->attunedItems()->count() }} / {{ App\Models\Character::ATTUNEMENT_LIMIT }}
            </p>

            <ul class="space-y-1 text-sm">
                @foreach ($character->itemEffects as $effect)
                    <li @class([
                        'rounded px-2 py-1',
                        'bg-page' => $active->has($effect->id),
                        'bg-page text-muted line-through' => ! $active->has($effect->id),
                    ])>
                        {{ $effect->describe() }}
                        @unless ($active->has($effect->id))
                            <span class="ml-1 text-xs no-underline">(non in sintonia)</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif
</div>
