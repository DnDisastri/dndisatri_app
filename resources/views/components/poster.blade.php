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

{{-- Per togliere la scritta al pulsante: `action=""`, non `:action="null"`
     (un null ricade sul default e la scritta torna). --}}

{{--
    Il manifesto: una card tutta immagine, col testo sopra. Al contrario di
    <x-card>, questa È il richiamo.

    Due varianti: `banner` (alta, nel carosello Home) e `tile` (quadrata, in
    griglia); il titolo resta uguale. L'angolo in basso a destra è vivo, gli
    altri tondi: è la firma di questa card.

    Il velo scuro è leggibilità, non decorazione: testo bianco su immagine
    caricata da un DM (può essere chiarissima). Senza immagine si ricade sul
    navy, così nel carosello le card non cambiano altezza.
--}}
@php
    $quadrata = $variant === 'tile';

    // `banner` usa `min-h` e non un rapporto fisso: nel carosello le card si
    // allungano alla più alta, e un rapporto litigherebbe. `tile` è quadrata,
    // ma senza `min-h`, o su telefono a due colonne (~170px) imporrebbe 320px.
    $forma = $quadrata ? 'aspect-square p-4' : 'min-h-80 p-5';

    // `line-clamp-2`: un titolo lungo sfonderebbe la forma e a sparire sarebbe
    // la pillola in fondo, non il titolo. Sul telefono (tile ~170px) il corpo scende.
    $titolo = $quadrata
        ? 'mt-4 text-base leading-tight sm:text-lg'
        : 'mt-8 text-2xl leading-tight';

    $pillola = $quadrata ? 'mt-4 px-4 py-2 text-xs' : 'mt-6 px-5 py-3 text-sm';
@endphp

{{-- `rounded-poster` (30 30 8 30), non `rounded-card`: la forma dei riquadri
     tutti-immagine. I due token stanno in app.css. --}}
<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'group relative flex flex-col justify-between overflow-hidden '
           .'rounded-poster rounded-br-poster-cut bg-primary text-on-primary transition '
           .'hover:opacity-95 '.$forma,
   ]) }}>

    @if ($image)
        <img src="{{ $image }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    @endif

    {{-- Sopra l'immagine, sotto il testo. --}}
    <span class="absolute inset-0 bg-gradient-to-b from-black/25 via-black/50 to-black/25"></span>

    {{-- Resta anche vuoto: con `justify-between` regge il posto, altrimenti il
         titolo salirebbe in cima. Centrato solo sulla tile; sul banner «Nuovo
         evento» in alto a sinistra è l'inizio della lettura. --}}
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

    {{-- `font-normal`: Bowlby One ha un solo peso, un altro dà un grassetto finto. --}}
    <span class="relative line-clamp-2 block text-center font-display font-normal text-white
                 [text-shadow:0_1px_4px_rgb(0_0_0/0.6)] {{ $titolo }}">
        {{ $title }}
    </span>

    {{-- La pillola è uno `span`, non un link: la card è già cliccabile (link in
         link è HTML che il browser aggiusta a modo suo). Senza scritta diventa
         un cerchio in basso a destra; con la scritta e senza freccia, si centra. --}}
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
