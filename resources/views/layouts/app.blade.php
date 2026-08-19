{{-- L'unico layout dell'applicazione, e serve due meccanismi diversi.

     Le pagine normali arrivano con `@extends` e riempiono `@yield('content')`.
     I componenti Livewire a pagina intera arrivano invece come componente, e
     il contenuto gli viene passato in `$slot`: Livewire 4 risolve il suo
     layout predefinito `layouts::app` proprio su questo file.

     Vanno stampati tutti e due. Se ne manca uno, quelle pagine escono con
     header e footer ma il corpo vuoto, senza il minimo errore. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
<body class="min-h-screen flex flex-col antialiased">
    @includeWhen(auth()->check(), 'partials.header')

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-4xl px-4 pt-4">
                <x-note>{{ session('status') }}</x-note>
            </div>
        @endif

        {{-- Il gemello scontento di `status`: un'azione che non si è potuta
             fare, con la ragione. Serve dove fra il caricamento della pagina e
             il clic il mondo può essere cambiato — un posto che si riempie
             mentre lo si stava assegnando. --}}
        @if (session('error'))
            <div class="mx-auto max-w-4xl px-4 pt-4">
                <x-note tone="danger">{{ session('error') }}</x-note>
            </div>
        @endif

        {{-- Il corpo arriva da una parte o dall'altra, mai da entrambe. --}}
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="px-4 pb-4 text-center text-xs text-muted">
        {{ config('app.name') }}
    </footer>

    {{-- Lo spazio che la barra in basso occupa: senza, la fine di ogni pagina
         finirebbe sotto le pillole e non si potrebbe leggere. --}}
    @auth
        <div class="h-20 shrink-0" aria-hidden="true"></div>
    @endauth

    {{-- Un DM abita un'altra app: la barra in basso cambia mestiere (Regia,
         serate, tavolo) invece di elencare Eroi/Mercato/Eventi. Gli admin no —
         loro amministrano dal Pannello e non conducono al tavolo. --}}
    @auth
        @include(auth()->user()->isDm() ? 'partials.nav-dm' : 'partials.nav')
    @endauth
</body>
</html>
