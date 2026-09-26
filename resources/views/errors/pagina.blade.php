{{--
    Il vestito delle pagine di servizio (P39, P40, P41).

    **Ha un documento suo e non usa `layouts.app`**, per una ragione che vale
    soprattutto per il 500: quel layout include l'intestazione, che conta le
    notifiche non lette — cioè fa una query. Se la pagina è finita in errore
    *perché* il database non risponde, la schermata d'errore andrebbe in errore
    a sua volta, e al posto della spiegazione si vedrebbe di nuovo il muro
    bianco di Laravel. Qui dentro non si legge niente da nessuna parte.

    Per la stessa ragione il ritorno è `url('/')` e non `route('home')`: non
    serve nemmeno il contenitore delle rotte per disegnarlo.

    Si include passando `codice`, `titolo`, `testo` e — dove ha senso — un
    `dettaglio`, che è la frase italiana scritta da chi ha chiuso la porta.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa', ['title' => $codice.': '.$titolo])
</head>
<body class="min-h-screen bg-page antialiased">

<div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12 text-center">

    {{-- Il numero è grande e spento: dice *quale* errore è a chi lo sa
         leggere, senza mettersi davanti alla frase che serve a tutti gli
         altri. Sopra sta il titolo, che è la cosa da leggere per prima. --}}
    <p class="font-display text-6xl text-muted">{{ $codice }}</p>

    <h1 class="mt-4 font-display text-2xl font-normal text-fg">{{ $titolo }}</h1>

    <p class="mt-3 text-sm leading-relaxed text-muted">{{ $testo }}</p>

    @if (! empty($dettaglio))
        <x-note tone="danger" class="mt-6 text-left">{{ $dettaglio }}</x-note>
    @endif

    <x-button variant="secondary" size="lg" full class="mt-8" href="{{ url('/') }}">
        Torna alla Home
    </x-button>
</div>

</body>
</html>
