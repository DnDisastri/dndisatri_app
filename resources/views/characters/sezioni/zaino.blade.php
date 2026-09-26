{{-- Nella vista pubblica il controller fornisce solo gli oggetti dichiarati scambiabili. --}}
@if (! $completa)
    <x-panel title="La sua vetrina">
        <p class="mb-3 text-sm text-muted">
            Quello che {{ $character->name }} ha segnato come scambiabile. Il
            resto dello zaino non si vede: per quello si chiede a parole.
        </p>

        @forelse ($character->items as $item)
            <x-inset padding="sm" class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                <span class="text-sm text-fg">
                    {{ $item->name }}
                    @if ($item->qty > 1)
                        <span class="text-muted">×{{ $item->qty }}</span>
                    @endif
                </span>
                <span class="text-xs text-muted">{{ $item->category }}</span>
            </x-inset>
        @empty
            <x-empty>Non ha messo niente in vetrina.</x-empty>
        @endforelse

        {{-- Preseleziona il proprietario della vetrina come destinatario dello scambio. --}}
        @if ($character->items->isNotEmpty() && Route::has('market.trades'))
            <p class="mt-3 border-t border-line pt-3">
                <a href="{{ route('market.trades', ['a' => $character->id]) }}"
                   class="text-sm text-muted hover:underline">
                    Proponigli uno scambio
                </a>
            </p>
        @endif
    </x-panel>
@else

<div class="grid gap-4 md:grid-cols-2">
    <livewire:inventory-manager :character="$character" :key="'inv-'.$character->id" />

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
                                    equipaggiato
                                </span>
                            </span>
                        @else
                            <span class="text-muted">niente</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p class="mt-2 border-t border-line pt-2 text-xs text-muted">
                Si indossa e si toglie dall'inventario qui accanto.
            </p>
        </x-panel>

        @if (auth()->id() === $character->user_id && $character->isAlive())
            <livewire:market.favorites :character="$character" :key="'pref-'.$character->id" />
        @endif

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
</div>

@endif
