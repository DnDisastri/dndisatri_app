@props([
    'nome',
    'meta' => null,
    'prezzo',
    'apri',
    'angolo' => null,
])

{{--
    La card di una cosa in vendita (emporio e annunci, la stessa vista da due lati).

    È un `<button>`, non una `<x-card href>`: apre il dettaglio, non porta
    altrove. La stella dei preferiti sta fuori dal pulsante, in `$angolo`
    (pulsante dentro pulsante non è HTML valido). `mt-auto` sul prezzo lo incolla
    in fondo, così in griglia le card restano alte uguali.
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
