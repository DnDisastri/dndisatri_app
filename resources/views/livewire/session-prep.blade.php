<div>
    {{-- Gli appunti privati del DM. Il combattimento (iniziativa, PF) sta nel
         suo tracker, accanto a questi. --}}
    <x-panel title="Appunti">
        <p class="-mt-1 mb-2 text-xs text-muted">
            Solo tuoi: i giocatori non li vedono. Non è il resoconto — quello viene dopo, e lo leggono loro.
        </p>

        <textarea wire:model.blur="note" rows="8" maxlength="20000"
                  class="w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg"
                  placeholder="Cosa deve succedere, chi dice cosa, il numero da non dimenticare."></textarea>

        @error('note') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror

        <div class="mt-2 flex items-center gap-3">
            <x-button wire:click="salvaNote">Salva gli appunti</x-button>

            @if ($noteSalvate)
                <span class="flex items-center gap-1 text-sm text-muted" wire:transition>
                    <x-icona :is="\App\Enums\Icon::Approve" class="h-4 w-4" /> Salvato
                </span>
            @endif
        </div>
    </x-panel>
</div>
