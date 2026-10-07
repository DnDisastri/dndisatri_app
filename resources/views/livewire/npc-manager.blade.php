<div>
<x-pagina class="space-y-6">
    @php $campo = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg'; @endphp

    <x-back dove="sopra" :href="route('dm.home', array_filter(['campagna' => $campagnaCorrente?->slug]))">
        Torna all'Area Master
    </x-back>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-wide text-muted">{{ $campagnaCorrente?->title ?? 'Nessuna campagna' }}</p>
            <h1 class="text-2xl text-fg">PNG della campagna</h1>
            <p class="mt-1 text-sm text-muted">Li vedono solo i DM.</p>
        </div>

        @if ($campagnaCorrente)
            <x-button type="button" wire:click="nuovo">Nuovo PNG</x-button>
        @endif
    </div>

    @if ($campagnaCorrente)
        <div class="relative">
            <x-icona :is="App\Enums\Icon::Search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.300ms="cerca" placeholder="Cerca per nome, luogo, note"
                   aria-label="Cerca fra i PNG"
                   class="w-full rounded-xl border border-line bg-surface py-2 pl-10 pr-3 text-fg placeholder:text-muted focus:border-active focus:outline-none">
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($elenco as $npc)
                <button type="button" wire:click="modifica({{ $npc->id }})"
                        @class([
                            'flex gap-3 rounded-card border border-line bg-surface p-3 text-left transition hover:border-active',
                            'opacity-60' => ! $npc->is_alive,
                        ])>
                    @if ($npc->photoUrl())
                        <img src="{{ $npc->photoUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-lg object-cover">
                    @endif
                    <span class="min-w-0">
                        <span class="block font-semibold text-fg">
                            {{ $npc->name }}
                            @unless ($npc->is_alive) <x-badge tone="neutral">morto</x-badge> @endunless
                        </span>
                        @if ($npc->location) <span class="block text-xs text-muted">{{ $npc->location }}</span> @endif
                        @if ($npc->wants) <span class="mt-1 block text-sm text-fg">Vuole: {{ $npc->wants }}</span> @endif
                    </span>
                </button>
            @empty
                <x-empty class="sm:col-span-2 xl:col-span-3">
                    {{ trim($cerca) !== '' ? "Nessun PNG che somigli a «{$cerca}»." : 'Nessun PNG ancora.' }}
                </x-empty>
            @endforelse
        </div>
    @else
        <x-empty>Non ci sono campagne attive.</x-empty>
    @endif
</x-pagina>

@if ($aperto)
    <x-modal :title="$modificaId ? 'Modifica PNG' : 'Nuovo PNG'" close="chiudi">
        <form wire:submit="salva" class="space-y-3 text-left text-sm">
            <label class="block">
                <span class="mb-1 block text-muted">Nome</span>
                <input type="text" maxlength="100" wire:model="png.name" class="{{ $campo }}">
                @error('png.name') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-muted">Dove si trova</span>
                <input type="text" maxlength="150" wire:model="png.location" class="{{ $campo }}">
            </label>

            <label class="block">
                <span class="mb-1 block text-muted">Cosa vuole</span>
                <input type="text" maxlength="255" wire:model="png.wants" class="{{ $campo }}">
            </label>

            <label class="block">
                <span class="mb-1 block text-muted">Note</span>
                <textarea rows="4" maxlength="5000" wire:model="png.notes" class="{{ $campo }}"></textarea>
            </label>

            <label class="block">
                <span class="mb-1 block text-muted">Foto (facoltativa)</span>
                <span class="mb-1 block text-xs text-muted">Meglio quadrata, almeno 400 × 400 px, fino a 4 MB.</span>
                <input type="file" accept="image/*" wire:model="foto" class="block w-full text-sm text-muted">
                @error('foto') <span class="mt-1 block text-on-danger-soft">{{ $message }}</span> @enderror
            </label>

            <label class="flex items-center gap-2 text-fg">
                <input type="checkbox" wire:model="png.is_alive" class="rounded border-line accent-[var(--ui-active)]">
                Vivo
            </label>

            <x-button full type="submit">Salva</x-button>

            @if ($modificaId)
                <button type="button" wire:click="elimina({{ $modificaId }})" wire:confirm="Eliminare questo PNG?"
                        class="w-full text-center text-sm font-semibold text-on-danger-soft hover:underline">
                    Elimina
                </button>
            @endif
        </form>
    </x-modal>
@endif
</div>
