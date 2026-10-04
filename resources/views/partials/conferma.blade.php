{{-- La apre app.js su un modulo con `data-conferma="domanda"`. Il padding sta sul div
     interno: un clic sul <dialog> stesso deve essere solo il fondo scuro. --}}
<dialog id="conferma" aria-labelledby="conferma-testo"
        class="w-[92vw] max-w-sm rounded-card border border-line bg-surface p-0 text-fg">
    <div class="p-5">
        <p id="conferma-testo" data-conferma-testo class="text-fg"></p>

        <div class="mt-5 flex justify-end gap-2">
            <x-button type="button" variant="quiet" data-conferma-no>Annulla</x-button>
            <x-button type="button" data-conferma-si>Elimina</x-button>
        </div>
    </div>
</dialog>
