@php
    // Voci in Support/Navigazione; da `lg` la sostituisce la barra laterale.
    $barra = \App\Support\Navigazione::barra(auth()->user());
    $etichetta = auth()->user()->isDm() ? 'Navigazione DM' : 'Navigazione principale';
@endphp

{{-- `z-30`: un menù appena aperto le passa sopra. Il padding in basso rispetta
     la barra dei gesti di iPhone e iPad. --}}
<nav class="pointer-events-none fixed inset-x-0 bottom-0 z-30 px-6 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden" aria-label="{{ $etichetta }}">
    <div class="pointer-events-auto mx-auto flex w-full max-w-xl items-center gap-3">

        @foreach ([$barra['sinistra'], null, $barra['destra']] as $gruppo)
            @if ($gruppo === null)
                @php $centro = $barra['centro']; @endphp

                <a @if ($centro['href']) href="{{ $centro['href'] }}" @endif
                   title="{{ $centro['nome'] }}" aria-label="{{ $centro['nome'] }}"
                   @if ($centro['attiva']) aria-current="page" @endif
                   @class([
                       'flex h-16 w-16 shrink-0 items-center justify-center rounded-full shadow-lg shadow-black/20 transition',
                       'bg-active text-on-active' => $centro['attiva'],
                       'bg-primary text-on-primary-soft hover:text-on-primary' => $centro['href'] && ! $centro['attiva'],
                       'bg-off text-off-fg cursor-default' => ! $centro['href'],
                   ])>
                    <x-icona :is="$centro['icona']" class="h-7 w-7" />
                </a>
            @else
                {{-- `justify-between`: icone agli estremi, con lo stesso margine del `p-1.5` su ogni lato. --}}
                <div class="flex flex-1 items-center justify-between rounded-full bg-primary p-1.5 shadow-lg shadow-black/20">
                    @foreach ($gruppo as $voce)
                        <a @if ($voce['href']) href="{{ $voce['href'] }}" @endif
                           title="{{ $voce['nome'] }}" aria-label="{{ $voce['nome'] }}"
                           @if ($voce['attiva']) aria-current="page" @endif
                           @class([
                               // `shrink-0`: su uno schermo stretto il cerchio attivo diventerebbe un ovale.
                               'flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition',
                               'bg-active text-on-active' => $voce['attiva'],
                               'text-on-primary-soft hover:text-on-primary' => $voce['href'] && ! $voce['attiva'],
                               'text-on-primary-soft opacity-40 cursor-default' => ! $voce['href'],
                           ])>
                            <x-icona :is="$voce['icona']" class="h-6 w-6" />
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</nav>
