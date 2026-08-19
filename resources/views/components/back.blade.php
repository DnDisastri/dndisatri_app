@props(['href', 'dove' => 'sotto'])

{{--
    Il ritorno: «Torna alle news», «Torna alla campagna».

    Era scritto a mano in quattro posti, con due spaziature diverse e nessuna
    freccia. La freccia non è decorazione: è l'unica cosa che si vede senza
    leggere, e «torna» e «vai» hanno lo stesso aspetto finché non ci si ferma a
    leggerli.

    **Guarda a sinistra ed è l'unica dell'applicazione a farlo** — `GoTo` punta
    a destra, `Discover` in diagonale. Tre direzioni per tre mestieri.

    La sottolineatura sta sul testo e non sul collegamento: passando sopra,
    un'unica riga sotto freccia e parole sembrerebbe barrare anche lo spazio in
    mezzo.

    **`dove` sceglie fra i due posti in cui ha senso stare**, e sono due mestieri
    diversi:

    - `sotto` (com'era, e resta il modo di dire «hai finito di leggere, ecco da
      dove sei venuto»). Centrato, perché in fondo alla pagina non compete con
      niente;
    - `sopra`, allineato a sinistra sotto l'intestazione: è la via d'uscita che
      si vuole **a portata**, senza scorrere fino in fondo. Su una pagina lunga
      — una scheda, un memoriale — il ritorno in fondo è il ritorno che nessuno
      trova.

    È una proprietà e non una classe per la stessa ragione di `<x-badge>`: sono
    assi che si sostituiscono, e `$attributes->merge()` invece accoda — due
    `text-center`/`text-left` nella stessa stringa si contendono il posto senza
    che l'ordine decida chi vince.
--}}
@php
    $posa = match ($dove) {
        'sopra' => 'mb-3',
        default => 'mt-8 text-center',
    };
@endphp

<p {{ $attributes->merge(['class' => $posa]) }}>
    <a href="{{ $href }}" class="group inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-fg">
        <x-icona :is="\App\Enums\Icon::Back" class="h-4 w-4 shrink-0" />
        <span class="group-hover:underline">{{ $slot }}</span>
    </a>
</p>
