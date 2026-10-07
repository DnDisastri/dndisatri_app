<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
{{-- Da disconnessi il pallino sull'icona dell'app si spegne. --}}
<body class="min-h-screen antialiased bg-page lg:grid lg:grid-cols-2" data-notifiche="0">
    <aside class="hidden flex-col items-center justify-center gap-5 bg-primary p-12 text-center lg:flex">
        @if (file_exists(public_path('logo.png')))
            <img src="{{ asset('logo.png') }}" alt="" class="h-28 w-28 rounded-card object-cover">
        @endif

        <h1 class="text-5xl text-on-primary">{{ config('app.name') }}</h1>
        <p class="text-lg text-on-primary-soft">Qui il caos vince sempre</p>
    </aside>

    <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-12 lg:px-8">
        @yield('top')

        {{-- Su desktop il marchio sta già nella metà di sinistra. --}}
        <div class="mb-8 rounded-2xl bg-surface border border-line p-6 text-center lg:hidden">
            <h1 class="text-3xl text-fg drop-shadow">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-muted">Qui il caos vince sempre</p>
        </div>

        <h2 class="mb-6 hidden text-3xl text-fg lg:block">@yield('title')</h2>

        @if (session('status'))
            <x-note class="mb-4">{{ session('status') }}</x-note>
        @endif

        @yield('content')
    </div>
</body>
</html>
