@props(['padding' => 'md'])

{{--
    Il riquadro dentro il riquadro.

    Una card sta **sopra** la pagina, quindi ha il fondo chiaro e un bordo; una
    scatola dentro una card fa il contrario — **rientra**, e per questo prende
    il colore della pagina e non ha bordo. È la citazione di uno scambio, il
    messaggio di chi propone, il blocco dei due inventari a confronto.

    Senza questo si finisce a mettere una card dentro una card, che sembra
    giusto e invece impila due bordi e due ombre e appiattisce tutto.
--}}
@php
    $imbottitura = match ($padding) {
        'sm' => 'px-3 py-2',
        default => 'p-3',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-[2px] bg-page '.$imbottitura]) }}>{{ $slot }}</div>
