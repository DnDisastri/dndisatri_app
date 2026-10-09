@extends('layouts.guest')
@section('title', 'Chiedi un posto')

@section('content')
    <x-panel class="mb-6">
        <p class="text-xs uppercase tracking-wide text-muted">{{ $session->campaign?->title }}</p>
        <p class="mt-1 text-lg font-semibold text-fg">{{ $session->displayTitle() }}</p>
        <p class="text-sm text-muted">{{ $session->played_at->translatedFormat('l j F \\a\\l\\l\\e H:i') }}</p>
    </x-panel>

    @if (session('inviata'))
        <x-note>
            Controlla la tua email: ti abbiamo mandato un link per confermare la richiesta.
            Senza quel clic il dungeon master non la vede.
        </x-note>
    @elseif (! $session->acceptsBookings())
        <x-note tone="danger">Questa sessione è già cominciata: le richieste sono chiuse.</x-note>
    @else
        @if (session('error'))
            <x-note tone="danger" class="mb-4">{{ session('error') }}</x-note>
        @endif

        <p class="mb-4 text-sm text-muted">
            Chiedi un posto per questa sessione: se c'è posto per te, ti arriverà un'email per confermarlo.
            Non serve un account.
        </p>

        <form method="POST" action="{{ route('guest-bookings.store', $session) }}" class="flex flex-col gap-4">
            @csrf

            @include('guest-bookings.partials.campi-ospite')

            <x-button size="lg">Chiedo un posto</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Vuoi vedere tutte le sessioni del mese? <a href="{{ route('guest-bookings.calendar') }}" class="font-semibold text-fg hover:underline">Apri il calendario</a>
        </p>
        <p class="mt-2 text-center text-sm text-muted">
            Hai un account? <a href="{{ route('login') }}" class="font-semibold text-fg hover:underline">Entra</a> e chiedi il posto col tuo personaggio.
        </p>
    @endif
@endsection
