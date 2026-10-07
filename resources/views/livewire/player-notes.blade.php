<details class="group" @if ($errors->has('testo')) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-fg [&::-webkit-details-marker]:hidden">
        <span class="min-w-0">
            Note sul giocatore <span class="font-normal text-muted">{{ $player->name }}</span>
            @if ($note->isNotEmpty())
                <span class="ml-1 rounded-full bg-accent-soft px-2 py-0.5 text-xs text-on-accent-soft">{{ $note->count() }}</span>
            @endif
        </span>
        <x-icona :is="\App\Enums\Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition group-open:rotate-180" />
    </summary>

    <div class="mt-3 space-y-3">
        <p class="text-xs text-muted">Le leggono solo i DM e gli admin, il giocatore no.</p>

        @foreach ($note as $nota)
            <div wire:key="nota-{{ $nota->id }}" class="rounded-md border border-line bg-page px-3 py-2 text-sm">
                <p class="whitespace-pre-line break-words text-fg">{{ $nota->body }}</p>
                <div class="mt-1 flex items-center justify-between gap-2 text-xs text-muted">
                    <span>{{ $nota->author?->name ?? 'Un DM' }}, {{ $nota->created_at->diffForHumans() }}</span>
                    @can('delete', $nota)
                        <button type="button" wire:click="elimina({{ $nota->id }})"
                                wire:confirm="Eliminare questa nota?"
                                class="font-semibold text-on-danger-soft hover:underline">Elimina</button>
                    @endcan
                </div>
            </div>
        @endforeach

        <form wire:submit="aggiungi" class="space-y-2">
            <label for="nota-giocatore-{{ $player->id }}" class="sr-only">Nuova nota sul giocatore</label>
            <textarea id="nota-giocatore-{{ $player->id }}" wire:model="testo" rows="2" maxlength="1000"
                      placeholder="Ruola molto, lascia spazio agli altri, sta un po' sulle sue…"
                      class="w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg"></textarea>
            @error('testo') <p class="text-sm text-on-danger-soft">{{ $message }}</p> @enderror
            <x-button size="sm" variant="secondary" type="submit">Aggiungi la nota</x-button>
        </form>
    </div>
</details>
