{{-- Le voci secondarie, le stesse nella tendina dell'intestazione e nella
     barra laterale: `$laterale` cambia solo la forma delle righe. --}}
@php
    $laterale ??= false;
    $riga = $laterale
        ? 'flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm transition'
        : 'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm';
    $stacco = $laterale ? 'mt-2 border-t border-line pt-2' : 'border-t border-line';
@endphp

@foreach (\App\Support\Navigazione::menu(auth()->user()) as $voce)
    <a href="{{ $voce['href'] }}" @if ($voce['attiva']) aria-current="page" @endif
       @class([$riga, 'text-fg hover:bg-page', 'bg-page font-semibold' => $voce['attiva']])>
        <x-icona :is="$voce['icona']" class="h-5 w-5 shrink-0 text-muted" />
        {{ $voce['nome'] }}
    </a>
@endforeach

{{-- Compare solo quando il browser dice che l'app si può installare: lo
     scopre app.js (evento beforeinstallprompt). --}}
<div class="{{ $stacco }}" data-installa hidden>
    <button type="button" class="{{ $riga }} text-fg hover:bg-page">
        <x-icona :is="\App\Enums\Icon::Install" class="h-5 w-5 shrink-0 text-muted" />
        Installa l'app
    </button>
</div>

{{-- iOS non ha il pulsante d'installazione: l'istruzione, mostrata da app.js
     solo su iPhone/iPad non già installati. --}}
<p data-ios-install hidden @class([$stacco, 'flex items-start gap-3 py-2.5 text-xs text-muted', $laterale ? 'px-3' : 'px-4'])>
    <x-icona :is="\App\Enums\Icon::Install" class="h-5 w-5 shrink-0" />
    <span>Per installare: tocca <span class="font-semibold text-fg">Condividi</span> e poi «Aggiungi a Home».</span>
</p>

{{-- Auto segue il tema di sistema; app.js aggiorna aria-pressed. --}}
<div @class([$stacco, 'px-4 py-3' => ! $laterale, 'px-3 pb-1' => $laterale])>
    <p class="mb-2 text-xs uppercase tracking-wide text-muted">Tema</p>

    <div class="flex gap-1" role="group" aria-label="Tema">
        @foreach (['auto' => 'Auto', 'light' => 'Chiaro', 'dark' => 'Scuro'] as $valore => $etichetta)
            <button type="button" data-tema="{{ $valore }}" aria-pressed="false"
                    class="flex-1 rounded-full border border-line px-2 py-1.5 text-xs font-semibold
                           text-fg transition hover:border-active
                           aria-pressed:border-active aria-pressed:bg-active aria-pressed:text-on-active">
                {{ $etichetta }}
            </button>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('logout') }}" class="{{ $stacco }}">
    @csrf
    <button type="submit" class="{{ $riga }} text-fg hover:bg-page">
        <x-icona :is="\App\Enums\Icon::Logout" class="h-5 w-5 shrink-0 text-muted" />
        Esci
    </button>
</form>
