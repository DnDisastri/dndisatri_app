@section('title', config('app.name').' Qui il caos vince sempre')
{{-- Landing autonoma: non usa i layout dell'app per permettere alle illustrazioni di occupare l'intero viewport. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
<body class="min-h-screen bg-page antialiased">

<div class="relative mx-auto flex min-h-screen max-w-md flex-col justify-end overflow-hidden">
{{-- Lo slider usa CSS scroll-snap; i pallini restano link funzionanti senza JS, che aggiorna solo lo stato attivo. --}}
    <div id="benvenuto"
         class="absolute inset-0 flex snap-x snap-mandatory overflow-x-auto scroll-smooth
                [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @forelse ($illustrazioni as $i => $illustrazione)
            <div id="benvenuto-{{ $i + 1 }}" class="h-full w-full shrink-0 snap-center">
{{-- Illustrazione decorativa: il contenuto testuale comunica già il messaggio. --}}
                <img src="{{ asset('images/prelogin/'.$illustrazione) }}" alt=""
                     class="h-full w-full object-cover">
            </div>
        @empty
            <div class="h-full w-full shrink-0 bg-primary"></div>
        @endforelse
    </div>

    <div class="relative h-28 bg-gradient-to-t from-page to-transparent"></div>

    <div class="relative bg-page px-6 pb-10 text-center">
        <div class="flex items-center justify-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-card bg-primary text-sm font-bold text-on-primary">D&D</span>

            <h1 class="font-display text-3xl font-normal text-fg">{{ config('app.name') }}</h1>
        </div>

        <p class="mt-5 text-sm leading-relaxed text-muted">
            Il destino ha tirato i dadi per te.<br>
            Ora tocca a te decidere cosa farne.<br>
            Prosegui, se l'avventura ti chiama.
        </p>

        @if (count($illustrazioni) > 1)
            <nav class="mt-6 flex justify-center gap-2" aria-label="Le illustrazioni">
                @foreach ($illustrazioni as $i => $illustrazione)
                    <a href="#benvenuto-{{ $i + 1 }}" data-pallino="{{ $i + 1 }}"
                       aria-label="Illustrazione {{ $i + 1 }}"
                       @if ($i === 0) aria-current="true" @endif
                       class="h-2.5 w-2.5 rounded-full bg-line transition
                              aria-[current]:w-6 aria-[current]:bg-active"></a>
                @endforeach
            </nav>
        @endif

        <x-button variant="secondary" size="lg" full class="mt-8" :href="route('login')">
            Tiriamo i Dadi
        </x-button>
    </div>
</div>

</body>
</html>
