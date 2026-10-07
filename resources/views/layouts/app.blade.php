{{-- Le pagine con `@extends` riempiono `content`, i Livewire a pagina intera arrivano in `$slot`
     (Livewire risolve `layouts::app` su questo file). Vanno stampati tutti e due:
     se ne manca uno quelle pagine escono vuote, senza errori. --}}
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

    {{-- Gli avvisi della sessione (status, error) li mostra <x-pagina>. --}}
    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="px-4 pb-4 text-center text-xs text-muted">
        {{ config('app.name') }}
    </footer>

    {{-- Lo spazio della barra in basso, o la fine della pagina finirebbe sotto le pillole. --}}
    @auth
        <div class="h-20 shrink-0 lg:hidden" aria-hidden="true"></div>

        @include('partials.nav')
    @endauth
</body>
</html>
