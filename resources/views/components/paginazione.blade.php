@props(['pagine', 'etichetta' => 'Pagine'])

{{-- Per `simplePaginate`: solo avanti e indietro, senza numeri di pagina. --}}
@if ($pagine->hasPages())
    <nav {{ $attributes->class('mt-4 flex items-center justify-between gap-3') }} aria-label="{{ $etichetta }}">
        @if ($pagine->onFirstPage())
            <span></span>
        @else
            <x-button variant="quiet" size="sm" :href="$pagine->previousPageUrl()">‹ Più recenti</x-button>
        @endif

        @if ($pagine->hasMorePages())
            <x-button variant="quiet" size="sm" :href="$pagine->nextPageUrl()">Più vecchie ›</x-button>
        @endif
    </nav>
@endif
