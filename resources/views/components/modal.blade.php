@props([
    'title' => null,
    'close' => 'chiudi',
])

{{--
    Il riquadro che si apre sopra la pagina.

    Serve dove aprire una pagina intera sarebbe troppo: l'articolo dell'emporio
    ha cinque campi, e andarci vuol dire perdere il posto nella griglia e
    tornare in cima all'elenco per ogni cosa che si guarda.

    **Non è un componente autonomo**: vive dentro un componente Livewire, che
    decide se disegnarlo (`@if`) e possiede il metodo per chiuderlo. `$close` è
    il nome di quel metodo — `chiudi` quasi sempre — e non una funzione
    Javascript: qui non c'è Alpine, e non serve.

    Si chiude in tre modi, che sono i tre che la gente prova: la crocetta, il
    fondo scuro, il tasto Esc. Il fondo è un `<button>` vero e non un `<div>`
    con un `wire:click` sopra, così ci si arriva anche col tabulatore e ha un
    nome per chi non lo vede.

    Sta a `z-50`, che è il gradino più alto della scala: un riquadro modale con
    la navigazione o l'intestazione ancora premibili sopra sarebbe una porta
    socchiusa.

    LA SCALA, e conviene tenerla tutta qui perché sono quattro righe sparse in
    quattro file:

    - `z-30` la barra in basso — arredamento fisso;
    - `z-40` l'intestazione e i menù a tendina delle card — roba che uno ha
      appena aperto, e che deve passare sopra al resto;
    - `z-50` questo.

    Il resto della pagina non ha z, e non deve prenderne: il momento in cui si
    comincia a metterne uno qua e uno là è il momento in cui la scala smette di
    valere e si finisce a rilanciare a 9999.
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
