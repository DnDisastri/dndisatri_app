@props([
    'tone' => 'neutral',
    'size' => 'sm',
])

{{--
    La pillola: una parola che qualifica quello che le sta accanto.

    Cinque toni: `neutral` (un fatto senza peso), `outline` (un passaggio
    intermedio: chiaro col bordo, si legge anche sul tema scuro), `accent` (da
    notare: difficoltà, conteggio), `danger` (andato storto), `own` (riguarda te:
    il tuo posto — navy, non crema, che sulla card quest è già la difficoltà).

    Tono e misura sono proprietà, non classi (`$attributes->merge()` accoda).
--}}
@php
    $misure = match ($size) {
        'md' => 'px-3 py-1 text-sm font-bold',
        default => 'px-2 py-0.5 text-xs font-semibold',
    };

    // `quiet` e non `off`: `off` è esente dal contrasto (veste le voci spente
    // della barra), ma una pillola «Conclusa» si legge (era 2,1:1 contro 4,5:1).
    $tinte = match ($tone) {
        'accent' => 'bg-accent-soft text-on-accent-soft',
        'danger' => 'bg-danger-soft text-on-danger-soft',
        'own' => 'bg-primary text-on-primary',
        'outline' => 'border border-line bg-surface text-fg',
        default => 'bg-quiet text-on-quiet',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-block rounded-full '.$misure.' '.$tinte]) }}>{{ $slot }}</span>
