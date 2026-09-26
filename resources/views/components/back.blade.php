@props(['href', 'dove' => 'sotto'])

{{--
    Il ritorno: «Torna alle news». La freccia punta a sinistra (l'unica: `GoTo`
    a destra, `Discover` in diagonale).

    `dove` sceglie il posto: `sotto` (centrato, in fondo alla pagina) o `sopra`
    (a sinistra sotto l'intestazione, a portata su una pagina lunga). È una
    proprietà e non una classe perché `$attributes->merge()` accoda.
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
