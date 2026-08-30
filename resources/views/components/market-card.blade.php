@props([
    'nome',
    'meta' => null,
    'prezzo',
    'apri',
    'angolo' => null,
])

{{--
    La card di una cosa in vendita, all'emporio e negli annunci.

    È un componente solo per tutte e due le bacheche perché sono la stessa cosa
    vista da due lati — un nome, una riga di contorno, un prezzo — e tenerle in
    due pezzi di markup separati le faceva già scivolare: gli annunci usavano
    `<x-card>` e l'emporio un `<div>` con il bordo scritto a mano.

    **È un `<button>` e non una `<x-card href>`**: apre il riquadro di dettaglio
    e non porta da nessuna parte, quindi non è un collegamento. La stella dei
    preferiti sta *fuori* dal pulsante, in `$angolo`, perché un pulsante dentro
    un altro pulsante non è HTML valido e i browser lo sbrogliano come vogliono.

    `mt-auto` sul prezzo lo incolla in fondo: in una griglia le card della
    stessa riga sono alte uguali, e senza, i prezzi ballerebbero a seconda della
    lunghezza dei nomi.
--}}
<div class="relative">
    <button type="button" wire:click="{{ $apri }}"
            class="flex h-full w-full flex-col rounded-card border border-line bg-surface p-3 text-left transition hover:border-active">
        <span @class(['font-semibold leading-tight text-fg', 'pr-7' => filled($angolo)])>{{ $nome }}</span>

        @if ($meta)
            <span class="mt-0.5 text-xs leading-tight text-muted">{{ $meta }}</span>
        @endif

        <span class="mt-auto pt-2 text-sm font-bold text-on-accent-soft">
            {{ number_format($prezzo, 0, ',', '.') }} mo
        </span>
    </button>

    @if (filled($angolo))
        <div class="absolute right-1.5 top-1.5">{{ $angolo }}</div>
    @endif
</div>
