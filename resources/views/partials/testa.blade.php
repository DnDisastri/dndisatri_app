{{--
    La testa del documento, uguale per tutte e tre le sagome: l'applicazione,
    le pagine d'accesso e la presentazione.

    Era scritta due volte, e la terza pagina l'avrebbe scritta una terza. Le
    cose che stanno qui — i caratteri, il tema prima del disegno — sono quelle
    che, se si dimenticano su una pagina sola, non danno **nessun** errore: si
    vede il carattere di sistema, o un lampo bianco su chi ha scelto il tema
    scuro, e uno se ne accorge per caso.

    `@yield('title')` funziona anche da qui dentro: i blocchi non hanno niente
    a che vedere con l'annidamento dei file.
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

{{-- La barra del browser segue il tema, come lo sfondo della pagina. --}}
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#f5f5f5">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#111111">

<title>@yield('title', $title ?? config('app.name'))</title>

{{-- I caratteri stanno in un foglio a parte, che `@vite` non conosce: senza
     questa riga il browser non li scarica nemmeno e ripiega sul carattere di
     sistema, senza dare il minimo segnale che qualcosa manchi. Va prima del
     resto perché porta con sé i preload. --}}
{{ Vite::fonts() }}
@vite(['resources/css/app.css', 'resources/js/app.js'])

@include('partials.tema')
