@props([
    'href' => null,
    'padding' => 'md',
    'flush' => false,
])

{{--
    Il riquadro: la scatola in cui sta quasi tutto.

    Diventa un `<a>` se gli si dà un `href`, e allora si accende al passaggio —
    esattamente come `<x-button>`, così non c'è una seconda regola da ricordare.
    Senza `href` è un `<div>` fermo, e non prende il `hover`: un riquadro che si
    illumina sotto il dito e poi non fa niente è una piccola bugia ripetuta ogni
    volta.

    `flush` taglia quello che esce dagli angoli arrotondati: serve alle card che
    cominciano con un'immagine a filo del bordo, che senza resterebbe quadrata
    sopra una scatola tonda.

    `padding="none"` è la via d'uscita per chi l'imbottitura se la vuole
    scegliere — la usa `<x-empty>`. **Non** si ottiene lo stesso passando
    `class="py-8"`: quella si accoderebbe al `p-4` del componente e vincerebbe
    una delle due a seconda dell'ordine nel foglio compilato, senza un errore.
    È la trappola che ci è costata `<x-icona>`.

    Quello che aggiunge davvero — `mb-6`, `col-span-full`, `w-72` — passa da
    `class` senza dare fastidio a niente.
--}}
@php
    $imbottitura = match ($padding) {
        'none' => '',
        'sm' => 'px-4 py-3',
        'lg' => 'p-6',
        default => 'p-4',
    };

    $classi = trim('rounded-card border border-line bg-surface '.$imbottitura
        .($flush ? ' overflow-hidden' : '')
        .($href ? ' block transition hover:border-active' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classi]) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => $classi]) }}>{{ $slot }}</div>
@endif
