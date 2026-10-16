@props(['pagine', 'etichetta' => 'Pagine'])

{{--
    La paginazione di tutta l'app. Con `paginate()` mostra i numeri: la prima,
    l'ultima e le vicine a quella aperta («‹ 1 … 4 5 6 … 12 ›»). Con
    `simplePaginate()`, che non conta le righe, solo avanti e indietro.
    Filtri e ancora li porta il paginatore: `withQueryString()` e `fragment()`.
--}}
@php
    $bottone = 'flex h-9 min-w-9 items-center justify-center rounded-full px-3 text-sm font-semibold transition';
    $spento = $bottone.' text-muted opacity-40';
    $attivo = $bottone.' text-fg hover:bg-quiet';
@endphp

@if ($pagine->hasPages())
    <nav {{ $attributes->class('mt-4 flex items-center justify-center gap-1') }} aria-label="{{ $etichetta }}">
        @if ($pagine->onFirstPage())
            <span class="{{ $spento }}" aria-hidden="true">‹</span>
        @else
            <a href="{{ $pagine->previousPageUrl() }}" rel="prev" class="{{ $attivo }}">
                ‹<span class="sr-only"> Più recenti</span>
            </a>
        @endif

        @if ($pagine instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            @php
                $ultima = $pagine->lastPage();
                $qui = $pagine->currentPage();
                $numeri = collect([1, $qui - 1, $qui, $qui + 1, $ultima])
                    ->filter(fn (int $n) => $n >= 1 && $n <= $ultima)
                    ->unique()
                    ->sort()
                    ->values();
            @endphp

            @foreach ($numeri as $i => $numero)
                @if ($i > 0 && $numero - $numeri[$i - 1] > 1)
                    <span class="px-1 text-sm text-muted" aria-hidden="true">…</span>
                @endif

                @if ($numero === $qui)
                    <span aria-current="page" class="{{ $bottone }} bg-active text-on-active">{{ $numero }}</span>
                @else
                    <a href="{{ $pagine->url($numero) }}" class="{{ $attivo }}" aria-label="Pagina {{ $numero }}">{{ $numero }}</a>
                @endif
            @endforeach
        @endif

        @if ($pagine->hasMorePages())
            <a href="{{ $pagine->nextPageUrl() }}" rel="next" class="{{ $attivo }}">
                <span class="sr-only">Più vecchie </span>›
            </a>
        @else
            <span class="{{ $spento }}" aria-hidden="true">›</span>
        @endif
    </nav>
@endif
