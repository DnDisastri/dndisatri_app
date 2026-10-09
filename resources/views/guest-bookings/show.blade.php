@extends('layouts.guest')
@section('title', 'La tua prenotazione')

@php
    use App\Enums\SeatStatus;

    $session = $posto->session;
    $stato = $posto->status;
    $aperta = $session->acceptsBookings();
@endphp

@section('content')
    {{-- Il giorno in grande, subito sotto il titolo: passando da una richiesta all'altra si vede che è cambiata. --}}
    <p class="-mt-3 mb-4 text-xl font-semibold text-fg">
        {{ ucfirst($session->played_at->translatedFormat('l j F')) }}
    </p>

    <x-panel class="mb-6">
        <p class="text-xs uppercase tracking-wide text-muted">{{ $session->campaign?->title }}</p>
        <p class="mt-1 text-lg font-semibold text-fg">{{ $session->displayTitle() }}</p>
        <p class="text-sm text-muted">{{ $session->played_at->translatedFormat('l j F \\a\\l\\l\\e H:i') }}</p>
    </x-panel>

    @if (session('error'))
        <x-note tone="danger" class="mb-4">{{ session('error') }}</x-note>
    @endif

    <p class="text-sm text-muted">Ciao {{ $posto->guest_name }},</p>
    <p class="mt-1 text-lg font-semibold text-fg">{{ $stato->mine() }}</p>

    <p class="mt-2 text-sm text-muted">
        @if (! $aperta)
            La sessione è già cominciata.
        @else
            @switch($stato)
                @case(SeatStatus::Unverified)
                    Apri il link che ti abbiamo mandato per email per confermare la richiesta.
                    @break
                @case(SeatStatus::Offered)
                    C'è un posto per te: è tuo se confermi entro
                    {{ $posto->offer_expires_at->translatedFormat('l j F \\a\\l\\l\\e H:i') }}.
                    @break
                @case(SeatStatus::Confirmed)
                    Il posto è tuo: ci vediamo alla sessione. Se non puoi più venire, disdici qui sotto.
                    @break
                @case(SeatStatus::Reserve)
                    Se si libera un posto, potresti essere chiamato. Non è garantito.
                    @break
                @case(SeatStatus::Expired)
                    Non hai confermato in tempo e il posto è tornato libero. Potrebbe esserti offerto di nuovo.
                    @break
                @case(SeatStatus::Withdrawn)
                    La richiesta è ritirata.
                    @break
                @default
                    @if ($posto->awaitsReserveAnswer())
                        I posti sono tutti confermati. Vuoi restare fra le riserve? Se qualcuno si ritira potresti essere chiamato, ma non è garantito.
                    @else
                        Hai chiesto un posto per questa sessione. Se c'è posto per te, ti arriverà un'email per confermarlo.
                    @endif
            @endswitch
        @endif
    </p>

    @if ($aperta)
        <div class="mt-6 flex flex-col gap-3">
            @if ($stato === SeatStatus::Offered)
                <form method="POST" action="{{ route('guest-bookings.answer-offer', $posto->guest_token) }}">
                    @csrf
                    <input type="hidden" name="risposta" value="si">
                    <x-button size="lg" class="w-full">Confermo il posto</x-button>
                </form>
                <form method="POST" action="{{ route('guest-bookings.answer-offer', $posto->guest_token) }}">
                    @csrf
                    <input type="hidden" name="risposta" value="no">
                    <x-button variant="quiet" class="w-full">Rinuncio</x-button>
                </form>
            @elseif ($posto->awaitsReserveAnswer())
                <form method="POST" action="{{ route('guest-bookings.answer-reserve', $posto->guest_token) }}">
                    @csrf
                    <input type="hidden" name="risposta" value="si">
                    <x-button size="lg" class="w-full">Resto fra le riserve</x-button>
                </form>
                <form method="POST" action="{{ route('guest-bookings.answer-reserve', $posto->guest_token) }}">
                    @csrf
                    <input type="hidden" name="risposta" value="no">
                    <x-button variant="quiet" class="w-full">No, grazie</x-button>
                </form>
            @elseif ($stato->isActive())
                <form method="POST" action="{{ route('guest-bookings.withdraw', $posto->guest_token) }}">
                    @csrf
                    <x-button variant="quiet" class="w-full">
                        {{ $stato === SeatStatus::Confirmed ? 'Disdico il posto' : 'Ritiro la richiesta' }}
                    </x-button>
                </form>
            @endif
        </div>
    @endif

    @if ($altre->isNotEmpty())
        <div class="mt-8 border-t border-line pt-4">
            <p class="text-xs uppercase tracking-wide text-muted">Le tue altre richieste</p>
            <ul class="mt-2 space-y-2">
                @foreach ($altre as $altra)
                    <li>
                        <a href="{{ $altra->guestUrl() }}"
                           class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface px-3 py-2 transition hover:border-active">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-fg">{{ $altra->session->displayTitle() }}</span>
                                <span class="block text-xs text-muted">{{ $altra->session->played_at->translatedFormat('l j F, H:i') }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-muted">{{ $altra->status->mine() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('guest-bookings.calendar') }}" class="font-semibold text-fg hover:underline">Le sessioni del mese</a>
    </p>
@endsection
