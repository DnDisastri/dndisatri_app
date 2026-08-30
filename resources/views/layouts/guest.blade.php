<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
{{-- Le pagine d'accesso hanno lo sfondo sfumato della vecchia applicazione. --}}
<body class="min-h-screen antialiased bg-page">
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12">
        <div class="mb-8 rounded-2xl bg-surface border border-line p-6 text-center">
            <h1 class="text-3xl text-fg drop-shadow">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-muted">Qui il caos vince sempre</p>
        </div>

        @if (session('status'))
            <x-note class="mb-4">{{ session('status') }}</x-note>
        @endif

        @yield('content')
    </div>
</body>
</html>
