@props([
    'variant' => 'primary',
    'size' => 'md',
    'full' => false,
    'href' => null,
    'disabled' => false,
])

{{--
    Il pulsante.

    Gli assi sono proprietà, non classi: `$attributes->merge()` accoda invece di
    sostituire (com'è costato a <x-icona>: `class="h-4 w-4"` dava `h-6 w-6 h-4
    w-4`). Quindi angoli, spaziature, colori, peso e larghezza si scelgono con
    `variant`, `size` e `full`; quello che si aggiunge senza contendere
    (`flex-1`, `mt-4`) passa da `class`.

    Con `href` è un `<a>`, altrimenti un `<button>`.
--}}
@php
    // Lo spento toglie anche il colore (`grayscale`): la sola opacità lasciava
    // un rosso pallido, che al sole è ancora un rosso.
    $base = 'inline-flex items-center justify-center gap-1 rounded-full font-semibold transition '
        .'disabled:cursor-not-allowed disabled:opacity-50 disabled:grayscale';

    $misure = match ($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-6 py-3',
        default => 'px-4 py-2 text-sm',
    };

    /*
     * Tre mestieri: `primary` (rosso) l'azione principale, una per schermata;
     * `secondary` (navy) un'azione pari; `quiet` (solo bordo) annulla, torna,
     * il servizio. I pieni si spengono al passaggio, il `quiet` si accende.
     */
    $tinte = match ($variant) {
        'secondary' => 'bg-primary text-on-primary hover:opacity-90',
        'quiet' => 'border border-line bg-surface text-fg hover:border-active',
        default => 'bg-active text-on-active hover:opacity-90',
    };

    $classi = trim($base.' '.$misure.' '.$tinte.($full ? ' w-full' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classi]) }}>{{ $slot }}</a>
@else
    {{-- `type` predefinito `submit`: quasi tutti stanno in un modulo. `disabled`
         è una proprietà, non un attributo di passaggio: `@disabled(...)` in un
         tag di componente non compila, e un `:disabled="false"` nel sacco degli
         attributi diventa `disabled=""`, che in HTML disabilita. --}}
    <button @disabled($disabled) {{ $attributes->merge(['type' => 'submit', 'class' => $classi]) }}>{{ $slot }}</button>
@endif
