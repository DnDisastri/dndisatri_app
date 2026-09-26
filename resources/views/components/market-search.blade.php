@props(['placeholder' => 'Cerca nel mercato'])

{{-- Ricerca live con `debounce` (senza, ogni lettera è un giro sul server).
     `type="search"`: sul telefono dà il tasto «cerca» e la crocetta per svuotare.
     La lente è un'etichetta, non un pulsante (`pointer-events-none`). --}}
<div {{ $attributes->merge(['class' => 'relative mb-4']) }}>
    <x-icona :is="\App\Enums\Icon::Search"
             class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />

    {{-- data-cerca: scroll al focus e chiusura tastiera su Invio, in app.js. --}}
    <input type="search" wire:model.live.debounce.300ms="cerca" data-cerca
           placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
           class="w-full rounded-xl border border-line bg-surface py-2 pl-10 pr-3 text-fg transition placeholder:text-muted focus:border-active focus:outline-none">
</div>
