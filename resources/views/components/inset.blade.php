@props(['padding' => 'md'])

{{--
    Il riquadro dentro il riquadro: rientra invece di stare sopra, quindi prende
    il colore della pagina e niente bordo. Una card dentro una card impilerebbe
    due bordi e due ombre.
--}}
@php
    $imbottitura = match ($padding) {
        'sm' => 'px-3 py-2',
        default => 'p-3',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-[2px] bg-page '.$imbottitura]) }}>{{ $slot }}</div>
