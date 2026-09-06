@props(['tone' => 'info'])

{{--
    Il messaggio: una riga che l'applicazione dice a chi guarda («serve un
    personaggio vivo», «non hai abbastanza oro»). Non è contenuto della pagina,
    quindi ha un fondo suo invece di stare in una card.

    Due toni: `info` (è andata bene, o c'è da sapere) e `danger` (non si è
    potuto fare, con la ragione).
--}}
@php
    $tinte = match ($tone) {
        'danger' => 'border-line bg-danger-soft text-on-danger-soft',
        default => 'border-line bg-accent-soft text-on-accent-soft',
    };
@endphp

<p {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm '.$tinte]) }}>{{ $slot }}</p>
