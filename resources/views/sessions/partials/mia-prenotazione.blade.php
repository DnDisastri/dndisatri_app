{{-- Una riga di «Le mie prenotazioni»: la sessione, lo stato e, se serve, la risposta da dare subito. --}}
@php
    use App\Enums\SeatStatus;

    $sessione = $posto->session;
@endphp

<x-panel class="space-y-2">
    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
        <a href="{{ route('sessions.show', $sessione) }}" class="min-w-0 hover:underline">
            <span class="block text-xs uppercase tracking-wide text-muted">{{ $sessione->campaign?->title }}</span>
            <span class="block font-semibold text-fg">{{ ucfirst($sessione->played_at->translatedFormat('l j F, H:i')) }}</span>
            <span class="block text-sm text-muted">
                {{ $sessione->displayTitle() }}@if ($posto->character) · con {{ $posto->character->name }}@endif
            </span>
        </a>

        <x-badge :tone="$posto->awaitsReserveAnswer() ? 'outline' : $posto->status->tone()" class="shrink-0">
            {{ $posto->awaitsReserveAnswer() ? 'Riserva?' : $posto->status->label() }}
        </x-badge>
    </div>

    @if ($nota)
        <p class="text-sm text-muted">{{ $nota }}</p>
    @endif

    @if ($posto->status === SeatStatus::Offered)
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('sessions.answer-offer', $sessione) }}">
                @csrf
                <input type="hidden" name="risposta" value="si">
                <x-button size="sm">Confermo il posto</x-button>
            </form>
            <form method="POST" action="{{ route('sessions.answer-offer', $sessione) }}"
                  data-conferma="Rinunci al posto? Passa a qualcun altro." data-conferma-azione="Rinuncio">
                @csrf
                <input type="hidden" name="risposta" value="no">
                <x-button variant="quiet" size="sm">Rinuncio</x-button>
            </form>
        </div>
    @elseif ($posto->awaitsReserveAnswer())
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('sessions.answer-reserve', $sessione) }}">
                @csrf
                <input type="hidden" name="risposta" value="si">
                <x-button size="sm">Resto fra le riserve</x-button>
            </form>
            <form method="POST" action="{{ route('sessions.answer-reserve', $sessione) }}">
                @csrf
                <input type="hidden" name="risposta" value="no">
                <x-button variant="quiet" size="sm">No, grazie</x-button>
            </form>
        </div>
    @endif
</x-panel>
