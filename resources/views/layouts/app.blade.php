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
{{-- `lg:pl-64` lascia il posto alla barra laterale, che è fissa. --}}
<body @class(['min-h-screen flex flex-col antialiased', 'lg:pl-64' => auth()->check()])>
    @auth
        @include('partials.header')
        @include('partials.barra-laterale')
    @endauth

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-6xl px-4 pt-4 md:px-6 lg:px-8">
                <x-note>{{ session('status') }}</x-note>
            </div>
        @endif

        {{-- Il gemello scontento di `status`: un'azione che non si è potuta
             fare, con la ragione. Serve dove fra il caricamento della pagina e
             il clic il mondo può essere cambiato — un posto che si riempie
             mentre lo si stava assegnando. --}}
        @if (session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4 md:px-6 lg:px-8">
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
         finirebbe sotto le pillole. Su desktop la barra non c'è. --}}
    @auth
        <div class="h-20 shrink-0 lg:hidden" aria-hidden="true"></div>

        @include('partials.nav')
    @endauth
</body>
</html>
