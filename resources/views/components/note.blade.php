@props(['tone' => 'info'])

{{--
    Il messaggio: una riga che l'applicazione dice a chi sta guardando.

    «Prenotato, il posto è tuo quando il dungeon master conferma», «serve un
    personaggio vivo per usare il mercato», «non hai abbastanza oro». Non è
    contenuto della pagina — è l'applicazione che parla — e per questo ha un
    fondo suo invece di stare in una card come tutto il resto.

    Due toni, e sono due cose diverse:

    - `info` — è andata bene, o c'è qualcosa da sapere;
    - `danger` — non si è potuto fare, con la ragione.

    Prima erano scritti in quattro modi: il mercato usava `rounded-xl` con il
    fondo crema, il riquadro di `session('status')` nel layout usava
    `rounded-md` e un testo navy, e quello degli errori un bordo rosso e nessun
    fondo. Tre vestiti per la stessa voce.
--}}
@php
    $tinte = match ($tone) {
        'danger' => 'border-line bg-danger-soft text-on-danger-soft',
        default => 'border-line bg-accent-soft text-on-accent-soft',
    };
@endphp

<p {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm '.$tinte]) }}>{{ $slot }}</p>
