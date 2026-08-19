@props([
    'variant' => 'primary',
    'size' => 'md',
    'full' => false,
    'href' => null,
    'disabled' => false,
])

{{--
    Il pulsante.

    Prima esisteva diciassette volte scritto in dieci modi: tutti lo stesso
    pulsante, ma quattro con `transition` e uno senza, uno `font-medium` e gli
    altri `font-semibold`. Nessuna di quelle differenze era una decisione —
    erano copie invecchiate ognuna per conto suo.

    **Gli assi sono proprietà, non classi.** È la lezione che ci è costata
    `<x-icona>`: passargli `class="h-4 w-4"` produceva `class="h-6 w-6 h-4 w-4"`
    e le icone piccole venivano disegnate grandi **senza il minimo errore**,
    perché `$attributes->merge()` **accoda e non sostituisce**. Da cui la regola:

    - quello che il pulsante possiede — angoli, spaziature, colori, peso del
      testo, transizione, larghezza piena — si sceglie con `variant`, `size` e
      `full`, e il chiamante non lo passa mai come classe;
    - quello che **si aggiunge** senza contendere niente — `flex-1`, `mt-4` —
      può arrivare da `class` e viene accodato, che lì è il comportamento
      giusto.

    Diventa un `<a>` se gli si dà un `href`, un `<button>` altrimenti: era una
    scelta ricopiata a mano in ogni pagina, con il `text-center` che ne
    conseguiva scritto ogni volta (e dimenticato qualche volta).
--}}
@php
    /*
     * Lo spento toglie **il colore** oltre alla forza: la sola opacità lo
     * lasciava rosso pallido, e al sole su un telefono un rosso pallido è
     * ancora un rosso. `grayscale` gli leva il mestiere, e si vede da lontano
     * che non c'è niente da premere.
     */
    $base = 'inline-flex items-center justify-center gap-1 rounded-full font-semibold transition '
        .'disabled:cursor-not-allowed disabled:opacity-50 disabled:grayscale';

    $misure = match ($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-6 py-3',
        default => 'px-4 py-2 text-sm',
    };

    /*
     * Tre gradini, e sono tre mestieri diversi — non tre colori a piacere:
     *
     * - `primary`, rosso: l'azione principale della pagina, quella verso cui
     *   si viene spinti. Ce n'è una sola per schermata;
     * - `secondary`, navy: un'azione pari, che non è però *quella*. È il
     *   contrasto già in uso fra «Danni» e «Cure», dove nessuna delle due è
     *   più importante dell'altra ma vanno distinte a colpo d'occhio;
     * - `quiet`, solo bordo: annullare, tornare indietro, il servizio.
     *
     * I pieni si spengono al passaggio, il `quiet` si accende: sul rosso e sul
     * navy non c'è spazio per accendere altro, mentre un bordo neutro che
     * diventa rosso è la stessa convenzione delle card.
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
    {{-- `type` predefinito a `submit`: quasi tutti i pulsanti dell'app stanno
         dentro un modulo, e un `type` dimenticato vale `submit` comunque. Chi
         ne vuole uno che non invii niente lo dichiara.

         `disabled` è una proprietà e non un attributo di passaggio, per due
         ragioni che si sommano. La prima è che `@disabled(...)` scritto dentro
         un tag di componente non arriva mai a compilarsi: il lettore degli
         attributi lo mangia e ne esce PHP rotto. La seconda è peggio — un
         `:disabled="false"` passato al sacco degli attributi verrebbe reso
         come `disabled=""`, che in HTML **disabilita**: un pulsante spento
         proprio quando doveva essere acceso, senza un errore. --}}
    <button @disabled($disabled) {{ $attributes->merge(['type' => 'submit', 'class' => $classi]) }}>{{ $slot }}</button>
@endif
