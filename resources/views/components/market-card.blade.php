@props([
    'nome',
    'meta' => null,
    'prezzo',
    'apri',
    'angolo' => null,
])

{{-- `prezzo` in rame. La stella sta in `$angolo`, fuori dal pulsante: pulsante dentro pulsante non è HTML valido. --}}
<div class="relative">
    <button type="button" wire:click="{{ $apri }}"
            class="flex h-full w-full flex-col rounded-card border border-line bg-surface p-3 text-left transition hover:border-active">
        <span @class(['font-semibold leading-tight text-fg', 'pr-7' => filled($angolo)])>{{ $nome }}</span>

        @if ($meta)
            <span class="mt-0.5 text-xs leading-tight text-muted">{{ $meta }}</span>
        @endif

        <x-monete :valore="$prezzo" class="mt-auto pt-2 text-sm font-bold text-on-accent-soft" />
    </button>

    @if (filled($angolo))
        <div class="absolute right-1.5 top-1.5">{{ $angolo }}</div>
    @endif
</div>
