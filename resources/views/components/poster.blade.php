@props([
    'href',
    'title',
    'image' => null,
    'label' => null,
    'meta' => null,
    'action' => 'Scopri tutti i dettagli',
    'icon' => true,
    'variant' => 'banner',
])

{{-- **Per togliere la scritta al pulsante si passa `action=""`, non
     `:action="null"`.** Blade risolve le proprietà con `??`, quindi un `null`
     ricade sul valore predefinito e la scritta torna: sembra che l'attributo
     venga ignorato. La stringa vuota invece passa, perché non è nulla. --}}

{{--
    Il manifesto: una card che è tutta immagine, con il testo sopra.

    È l'opposto di `<x-card>` e serve a un mestiere diverso. La card è un
    contenitore neutro che non vuole rubare l'attenzione a quello che ha
    dentro; questa **è** il richiamo, e per questo si prende tutto lo spazio e
    scrive sopra la foto.

    **Due forme, e sono due mestieri:**

    - `banner` — alta e larga, con il titolo in grande. Sta nel carosello della
      Home, dove le card scorrono una per volta e c'è spazio per leggere;
    - `tile` — quadrata e più compatta. Sta in griglia, dove si guarda a colpo
      d'occhio e si sceglie.

    Quello che **non** cambia fra le due è il titolo: sempre centrato e sempre
    nel carattere dei titoli. È la stessa cosa che si legge nei due posti, e
    vestirla in due modi la farebbe sembrare due cose diverse.

    **L'angolo in basso a destra è vivo, gli altri tre sono tondi.** Non è un
    vezzo che si può dimenticare: è il segno che distingue questa card da tutte
    le altre, e basta averne due appaiate per vedere che è voluto.

    **Il velo scuro non è decorazione, è leggibilità.** Il testo è bianco e
    l'immagine la carica un dungeon master: può essere una foto chiarissima di
    un tavolo al sole. Senza il velo quel giorno il titolo sparisce, e nessuno
    se ne accorge finché non succede. Il gradiente è più fitto in alto e in
    basso, dove sta il testo, e si alleggerisce in mezzo per non spegnere
    l'immagine del tutto. L'ombra sul testo è la seconda rete: costa niente e
    salva il caso della foto quasi bianca.

    Senza immagine si ricade sul navy invece di lasciare un buco: una card che
    a volte è alta e a volte no farebbe saltare la fila del carosello.
--}}
@php
    $quadrata = $variant === 'tile';

    /*
     * `banner` usa `min-h` e non un rapporto fisso: dentro un carosello le card
     * si allungano tutte alla più alta, e un rapporto litigherebbe con quello.
     * `tile` invece sta in griglia, dove il quadrato è proprio la richiesta —
     * e lì il minimo va tolto, o su un telefono a due colonne (che dà card da
     * 170px) imporrebbe 320px di altezza e il quadrato salterebbe.
     */
    $forma = $quadrata ? 'aspect-square p-4' : 'min-h-80 p-5';

    /*
     * Del titolo cambia solo la misura, e con lei quanto spazio gli si dà
     * intorno. Carattere e allineamento stanno nella classe comune più sotto.
     *
     * `line-clamp-2` non è prudenza: la card ha una forma decisa — quadrata o
     * alta che sia — e un titolo di quattro righe la sfonda. Siccome il
     * manifesto ritaglia quello che esce, a sparire non è il titolo: è la
     * pillola in fondo, tagliata a metà senza che il titolo ci guadagni nulla.
     * Due righe con i puntini sono una perdita onesta, perché il titolo intero
     * sta a un tocco — sulla pagina dove uno stava andando comunque.
     *
     * Sul telefono il corpo scende: in griglia a due colonne su uno schermo da
     * 375px la card viene 170×170, e a quella misura il titolo grande non ci
     * sta nemmeno in due righe.
     */
    $titolo = $quadrata
        ? 'mt-4 text-base leading-tight sm:text-lg'
        : 'mt-8 text-2xl leading-tight';

    $pillola = $quadrata ? 'mt-4 px-4 py-2 text-xs' : 'mt-6 px-5 py-3 text-sm';
@endphp

{{-- `rounded-poster` e **non** `rounded-card`: 30, 30, 8, 30 è la forma dei
     riquadri che sono tutti immagine — manifesti e mappe — e non segue il
     raggio delle card. Il perché dei due token sta in `app.css`. --}}
<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'group relative flex flex-col justify-between overflow-hidden '
           .'rounded-poster rounded-br-poster-cut bg-primary text-on-primary transition '
           .'hover:opacity-95 '.$forma,
   ]) }}>

    @if ($image)
        <img src="{{ $image }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    @endif

    {{-- `aria-hidden` non serve: è un elemento vuoto e i lettori di schermo lo
         saltano da soli. Sta sopra l'immagine e sotto il testo. --}}
    <span class="absolute inset-0 bg-gradient-to-b from-black/75 via-black/40 to-black/75"></span>

    {{-- Questo blocco resta anche quando è vuoto, e non è una svista: la card
         dispone tre cose con `justify-between`, e togliendone una il titolo
         smetterebbe di stare in mezzo e salirebbe in cima. Vuoto non occupa
         niente e tiene il posto. --}}
    {{-- **Centrata solo sulla quadrata.** Su una tile lo spazio è poco e tutto
         il resto sta in mezzo — titolo e pillola — quindi l'etichetta a
         sinistra era l'unica cosa fuori asse. Sul banner del carosello invece
         no: è alta e larga, «Nuovo evento» in alto a sinistra è l'inizio della
         lettura, e centrarlo lo faceva sembrare un secondo titolo. --}}
    <span @class([
        'relative [text-shadow:0_1px_3px_rgb(0_0_0/0.55)]',
        'text-center' => $quadrata,
    ])>
        @if ($label)
            <span class="block text-lg font-bold text-white">{{ $label }}</span>
        @endif

        @if ($meta)
            <span class="mt-0.5 block text-sm text-white/85">{{ $meta }}</span>
        @endif
    </span>

    {{-- `font-normal` non è una dimenticanza: Bowlby One ha **un solo peso**, e
         chiedergliene un altro non lo ingrassa — il browser disegna un
         grassetto finto, che su un carattere già pesantissimo si impasta. È la
         stessa nota che vale per `h1` e `h2` nel foglio di stile. --}}
    <span class="relative line-clamp-2 block text-center font-display font-normal text-white
                 [text-shadow:0_1px_4px_rgb(0_0_0/0.6)] {{ $titolo }}">
        {{ $title }}
    </span>

    {{-- La pillola è un `span` e non un secondo collegamento: la card **intera**
         è già cliccabile, e un link dentro un link è HTML che il browser
         aggiusta come gli pare.

         **Senza scritta diventa un cerchio.** Una pillola larga con dentro
         solo una freccia sarebbe un pulsante quasi vuoto; togliendo la parola
         si toglie anche la larghezza, e resta il gesto. Va in fondo a destra
         perché è lì che il pollice arriva e perché è la direzione in cui punta
         la freccia.

         Con la scritta e senza freccia, invece, il testo si centra: lasciato a
         sinistra resterebbe appeso a un vuoto che prima era occupato. --}}
    @if (filled($action))
        <span @class([
            'relative flex items-center gap-3 rounded-full bg-surface font-semibold text-fg',
            $pillola,
            'justify-between' => $icon,
            'justify-center' => ! $icon,
        ])>
            {{ $action }}

            @if ($icon)
                <x-icona :is="\App\Enums\Icon::Discover" class="h-5 w-5 shrink-0" />
            @endif
        </span>
    @else
        <span class="relative mt-4 flex h-11 w-11 shrink-0 items-center justify-center self-end
                     rounded-full bg-surface text-fg">
            <x-icona :is="\App\Enums\Icon::Discover" class="h-5 w-5" />
        </span>
    @endif
</a>
