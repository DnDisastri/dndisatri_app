@props(['is'])

{{-- La difficoltà di una quest: la pillola con le tacche del segnale a sinistra. --}}
<x-badge tone="accent" {{ $attributes->class('inline-flex items-center gap-1') }}>
    <x-icona :is="$is" class="h-3.5 w-3.5 shrink-0" />
    {{ $is->label() }}
</x-badge>
