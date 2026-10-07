@extends('layouts.app')
@section('title', 'Manuale')

@section('content')
@php
    use App\Enums\Icon;

    $percentuale = (int) ($campagna?->price_modifier ?? 0);
    $sezione = 'rounded-card border border-line bg-surface px-4 py-3';
@endphp

<x-pagina class="space-y-6">
    <x-back dove="sopra" :href="route('dm.home', array_filter(['campagna' => $campagna?->slug]))">
        Torna all'Area Master
    </x-back>

    <div>
        <p class="text-xs uppercase tracking-wide text-muted">{{ $campagna?->title ?? 'Senza campagna' }}</p>
        <h1 class="text-2xl text-fg">Manuale</h1>
        <p class="mt-1 text-sm text-muted">
            Prezzi del manuale base, per quello che i personaggi comprano in gioco. L'Emporio della gilda ha i suoi.
            @if ($percentuale !== 0)
                In questa campagna i prezzi sono {{ $percentuale > 0 ? '+' : '' }}{{ $percentuale }}%.
            @endif
        </p>
    </div>

    <section class="space-y-3" data-listino>
        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_14rem]">
            <div class="relative">
                <x-icona :is="Icon::Search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
                <input type="search" data-listino-cerca placeholder="Cerca un prezzo" aria-label="Cerca nel listino"
                       class="w-full rounded-xl border border-line bg-surface py-2 pl-10 pr-3 text-fg placeholder:text-muted focus:border-active focus:outline-none">
            </div>
            <select data-listino-categoria aria-label="Categoria"
                    class="rounded-xl border border-line bg-surface px-3 py-2 text-fg">
                <option value="">Tutte le categorie</option>
                @foreach ($listino->keys() as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </div>

        @foreach ($listino as $categoria => $voci)
            <div data-listino-gruppo="{{ $categoria }}" class="{{ $sezione }}">
                <h2 class="mb-2 text-xs uppercase tracking-wide text-muted">{{ $categoria }}</h2>
                <ul class="divide-y divide-line text-sm">
                    @foreach ($voci as $voce)
                        <li data-listino-voce="{{ mb_strtolower($voce['nome']) }}"
                            class="flex items-baseline justify-between gap-3 py-1.5">
                            <span class="text-fg">{{ $voce['nome'] }}</span>
                            <span class="shrink-0 text-right">
                                <strong class="text-on-accent-soft"><x-monete :valore="$voce['campagna']" /></strong>
                                @if ($voce['campagna'] !== $voce['base'])
                                    <span class="block text-xs text-muted">base <x-monete :valore="$voce['base']" /></span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <p data-listino-vuoto hidden class="text-sm text-muted">Niente che somigli a quello che cerchi.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-xs uppercase tracking-wide text-muted">Consultazione rapida</h2>

        <details class="{{ $sezione }}">
            <summary class="cursor-pointer font-semibold text-fg">Condizioni</summary>
            <dl class="mt-2 divide-y divide-line text-sm">
                @foreach ($condizioni as $condizione)
                    <div class="py-1.5">
                        <dt class="font-semibold text-fg">{{ $condizione['nome'] }}</dt>
                        <dd class="text-muted">{{ $condizione['testo'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </details>

        <details class="{{ $sezione }}">
            <summary class="cursor-pointer font-semibold text-fg">CD tipiche</summary>
            <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-3">
                @foreach ($cd as $nome => $valore)
                    <div class="flex justify-between gap-2">
                        <dt class="text-muted">{{ $nome }}</dt>
                        <dd class="font-semibold text-fg">{{ $valore }}</dd>
                    </div>
                @endforeach
            </dl>
        </details>

        <details class="{{ $sezione }}">
            <summary class="cursor-pointer font-semibold text-fg">Velocità di viaggio</summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-muted">
                        <tr><th class="py-1 pr-3">Passo</th><th class="py-1 pr-3">Minuto</th><th class="py-1 pr-3">Ora</th><th class="py-1 pr-3">Giorno</th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($viaggio as $passo => $riga)
                            <tr>
                                <td class="py-1.5 pr-3 font-semibold text-fg">{{ $passo }}</td>
                                <td class="py-1.5 pr-3 text-fg">{{ $riga['minuto'] }}</td>
                                <td class="py-1.5 pr-3 text-fg">{{ $riga['ora'] }}</td>
                                <td class="py-1.5 pr-3 text-fg">{{ $riga['giorno'] }}</td>
                            </tr>
                            @if ($riga['nota'])
                                <tr><td colspan="4" class="pb-1.5 text-xs text-muted">{{ $riga['nota'] }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        <details class="{{ $sezione }}">
            <summary class="cursor-pointer font-semibold text-fg">Stile di vita (al giorno)</summary>
            <dl class="mt-2 divide-y divide-line text-sm">
                @foreach ($stili as $nome => $costo)
                    <div class="flex justify-between gap-2 py-1.5">
                        <dt class="text-fg">{{ $nome }}</dt>
                        <dd class="text-fg">
                            @if ($costo === 0) nulla @else <x-monete :valore="$campagna?->adjustedPrice($costo) ?? $costo" /> @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </details>
    </section>
</x-pagina>
@endsection
