@props(['placeholder' => 'Cerca nel mercato'])

{{--
    La ricerca del mercato.

    Sta in cima a tutte e due le bacheche e scrive sempre nella stessa proprietà
    (`cerca`): due pagine che cercano la stessa cosa in due modi diversi
    sarebbero due abitudini da imparare invece di una.

    **Cerca mentre si scrive**, con un fiato di ritardo: senza `debounce` ogni
    lettera sarebbe un giro sul server, e a metà parola i risultati sono ancora
    rumore. Con più di trecento millisecondi comincia invece a sembrare rotta.

    `type="search"` perché sul telefono cambia il tasto invio in «cerca» e
    aggiunge la crocetta per svuotare — due cose che non si possono fare a mano.
    La lente sta dentro il campo e non accanto: è un'etichetta, non un pulsante,
    e infatti non si preme (`pointer-events-none`).
--}}
<div {{ $attributes->merge(['class' => 'relative mb-4']) }}>
    <x-icona :is="\App\Enums\Icon::Search"
             class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />

    <input type="search" wire:model.live.debounce.300ms="cerca"
           placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
           class="w-full rounded-xl border border-line bg-surface py-2 pl-10 pr-3 text-fg transition placeholder:text-muted focus:border-active focus:outline-none">
</div>
