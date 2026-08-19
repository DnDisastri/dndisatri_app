@props([
    'tone' => 'neutral',
    'size' => 'sm',
])

{{--
    La pillola: una parola sola che qualifica quello che le sta accanto —
    «Difficile», «Conclusa», «Caduto».

    I tre toni sono tre mestieri diversi, e prima erano mescolati: la difficoltà
    di una quest e lo stato di una campagna usavano fondi diversi pur dicendo
    la stessa cosa, cioè «ecco com'è fatta».

    - `neutral` — un fatto, senza peso: conclusa, archiviata, in attesa;
    - `accent` — qualcosa da notare: la difficoltà, il conteggio;
    - `danger` — qualcosa che è andato storto o è finito male;
    - `own` — **questo riguarda te**: il tuo posto a una quest. È il navy, la
      stessa tinta della reaction che hai scelto tu, e non il crema: sulla card
      di una quest il crema è già preso dalla difficoltà, e due pillole crema
      che dicono due cose senza rapporto si leggono come una sola.

    Come per `<x-button>`, tono e misura sono proprietà e non classi: sono assi
    che si sostituiscono, e `$attributes->merge()` invece accoda. Quello che si
    aggiunge davvero — `shrink-0`, `mt-1` — passa da `class` senza problemi.
--}}
@php
    $misure = match ($size) {
        'md' => 'px-3 py-1 text-sm font-bold',
        default => 'px-2 py-0.5 text-xs font-semibold',
    };

    /*
     * Il neutro usa `quiet` e **non** `off`, che pure ha lo stesso fondo. La
     * coppia `off` è dichiarata esente dai requisiti di contrasto perché
     * veste le voci spente della barra, che non sono da leggere ma da
     * riconoscere; una pillola «Conclusa» invece si legge eccome, e con quel
     * colore stava a 2,1:1 in chiaro contro i 4,5:1 che servono.
     */
    $tinte = match ($tone) {
        'accent' => 'bg-accent-soft text-on-accent-soft',
        'danger' => 'bg-danger-soft text-on-danger-soft',
        'own' => 'bg-primary text-on-primary',
        default => 'bg-quiet text-on-quiet',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-block rounded-full '.$misure.' '.$tinte]) }}>{{ $slot }}</span>
