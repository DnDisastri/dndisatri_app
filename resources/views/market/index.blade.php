@extends('layouts.app')
@section('title', \App\Http\Controllers\MarketController::SEZIONI[$sezione].' · Mercato')

@section('content')
@php $sezioni = \App\Http\Controllers\MarketController::SEZIONI; @endphp

<x-pagina class="space-y-4">
    <h2 class="flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::Market" class="h-7 w-7" /> Mercato
    </h2>

    {{-- Sezioni a scorrimento come nella scheda (app.js): l'indirizzo segue la
         sezione, così un refresh riapre lì. --}}
    <nav class="flex overflow-x-auto rounded-full border border-line bg-surface text-sm" aria-label="Sezioni del mercato">
        @foreach ($sezioni as $rotta => $nome)
            <button type="button" data-market-tab data-url="{{ route($rotta) }}" data-titolo="{{ $nome }} · Mercato"
                @if ($rotta === $sezione) aria-current="page" @endif
                class="flex-1 whitespace-nowrap rounded-full px-3 py-2 text-center font-medium text-muted
                       transition hover:text-fg aria-[current]:bg-primary aria-[current]:text-on-primary">
                {{ $nome }}
            </button>
        @endforeach
    </nav>

    <div id="market-slider"
        class="flex gap-6 snap-x snap-mandatory overflow-x-auto transition-[height] duration-200
               [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <div class="w-full shrink-0 snap-center snap-always self-start">
            <livewire:market.shop />
        </div>
        <div class="w-full shrink-0 snap-center snap-always self-start">
            <livewire:market.listings />
        </div>
        <div class="w-full shrink-0 snap-center snap-always self-start">
            <livewire:market.trades />
        </div>
    </div>

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>
@endsection
