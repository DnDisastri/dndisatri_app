@props([
    'title' => null,
    'close' => 'chiudi',
])

{{--
    Il riquadro che si apre sopra la pagina, dove aprire una pagina intera
    sarebbe troppo.

    Non è autonomo: vive dentro un componente Livewire che decide se disegnarlo
    e possiede il metodo per chiuderlo (`$close`, non una funzione JS: qui non
    c'è Alpine). Si chiude in tre modi: crocetta, fondo scuro, Esc. Il fondo è
    un `<button>` vero, così è raggiungibile col tabulatore.

    Scala z: `z-30` la barra in basso, `z-40` intestazione e tendine, `z-50`
    questo. Il resto della pagina non prende z.
--}}
<div class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true"
     wire:keydown.escape.window="{{ $close }}">

    <button type="button" wire:click="{{ $close }}"
            class="absolute inset-0 bg-black/60" aria-label="Chiudi"></button>

    <div {{ $attributes->merge(['class' => 'relative flex max-h-[85vh] w-full max-w-md flex-col overflow-y-auto rounded-card border border-line bg-surface p-5']) }}>
        <div class="mb-3 flex items-start justify-between gap-3">
            @if ($title)
                <h3 class="text-lg font-semibold text-fg">{{ $title }}</h3>
            @endif

            {{-- La crocetta sta in alto a destra anche senza titolo: è il posto
                 dove la si cerca, e spostarla per una riga in meno vorrebbe
                 dire cercarla due volte. --}}
            <button type="button" wire:click="{{ $close }}" aria-label="Chiudi"
                    class="-mr-1 -mt-1 ml-auto shrink-0 rounded-full p-1 text-muted transition hover:text-fg">
                <x-icona :is="\App\Enums\Icon::Close" class="h-5 w-5" />
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
