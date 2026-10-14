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

        <livewire:equipment-summary :character="$character" :key="'equip-'.$character->id" />

        @if (auth()->id() === $character->user_id && $character->isAlive())
            <livewire:market.favorites :character="$character" :key="'pref-'.$character->id" />
        @endif
    </div>
</div>

@endif
