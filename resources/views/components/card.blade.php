@props([
    'href' => null,
    'padding' => 'md',
    'flush' => false,
])

{{--
    Il riquadro: la scatola in cui sta quasi tutto.

    Con `href` diventa un `<a>` che si accende al passaggio (come <x-button>);
    senza, è un `<div>` fermo che non prende hover.

    `flush` taglia quello che esce dagli angoli (per le card che iniziano con
    un'immagine a filo). `padding="none"` toglie l'imbottitura (la usa
    <x-empty>): non equivale a `class="py-8"`, che si accoderebbe al `p-4` e
    vincerebbe a seconda dell'ordine nel foglio.
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
