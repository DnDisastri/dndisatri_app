{{-- Il modale della nota di diario su una sessione. Si chiude solo dai suoi pulsanti.
     Un errore ha il suo sacchetto per sessione: riapre il modale giusto, col testo scritto. --}}
@php
    $dialogo = 'nota-'.$sessione->id;
    $errore = $errors->getBag('diario-'.$sessione->id)->first('body');
    $campo = 'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg';
@endphp

<dialog id="{{ $dialogo }}" data-dialogo @if ($errore) data-apri-subito @endif aria-labelledby="{{ $dialogo }}-titolo"
        class="w-[92vw] max-w-md rounded-card border border-line bg-surface p-0 text-fg">
    <div class="relative p-5">
        <button type="button" data-chiudi-dialogo aria-label="Chiudi"
                class="absolute right-3 top-3 rounded-full p-1.5 text-muted transition hover:text-fg">
            <x-icona :is="\App\Enums\Icon::Close" class="h-5 w-5" />
        </button>

        <p class="pr-8 text-xs text-muted">{{ $sessione->played_at->translatedFormat('j F Y') }} · {{ $sessione->campaign?->title }}</p>
        <h4 id="{{ $dialogo }}-titolo" class="mt-1 pr-8 font-semibold text-fg">{{ $sessione->displayTitle() }}</h4>

        @if ($daRegistro)
            <a href="{{ route('sessions.show', $sessione) }}" class="text-xs font-semibold text-active hover:underline">Apri la sessione ›</a>
        @else
            <a href="{{ route('characters.ledger', $character) }}#sessioni" class="text-xs font-semibold text-active hover:underline">Tutto il diario di {{ $character->name }} ›</a>
        @endif

        @if ($scrive)
            <form method="POST" action="{{ route('characters.journal', [$character, $sessione]) }}" class="mt-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="ancora" value="{{ $daRegistro ? 'sessioni' : 'diario' }}">
                <x-testo-contato name="body" rows="6" :max="\App\Models\SessionJournal::MAX" :value="$errore ? old('body') : $nota"
                                 class="{{ $campo }}" aria-label="La tua nota"
                                 placeholder="Cosa ricordare di questa sessione: nomi, indizi, promesse." />
                @if ($errore) <p class="mt-1 text-sm text-on-danger-soft">{{ $errore }}</p> @endif
                <div class="mt-3 flex justify-end gap-2">
                    <x-button type="button" variant="quiet" data-chiudi-dialogo>Annulla</x-button>
                    <x-button>Salva</x-button>
                </div>
            </form>
        @else
            <p class="mt-3 whitespace-pre-line text-sm {{ filled($nota) ? 'text-fg' : 'italic text-muted' }}">{{ $nota ?: 'Il giocatore non ha scritto niente su questa sessione.' }}</p>
            <div class="mt-4 flex justify-end">
                <x-button type="button" variant="quiet" data-chiudi-dialogo>Chiudi</x-button>
            </div>
        @endif
    </div>
</dialog>
