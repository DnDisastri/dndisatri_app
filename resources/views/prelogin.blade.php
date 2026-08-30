@section('title', config('app.name').' Qui il caos vince sempre')
{{-- Landing a tutto viewport, senza layout dell'app. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
<body class="flex h-[100dvh] items-center justify-center overflow-hidden bg-page antialiased">

{{-- Mobile: la colonna riempie lo schermo. Schermi grandi: resta a proporzioni
     telefono (9:16) e il grigio attorno è un segnaposto per una futura immagine. --}}
<div class="relative h-full w-full overflow-hidden bg-primary sm:aspect-[9/16] sm:h-full sm:w-auto sm:max-w-full">
    <div id="benvenuto"
         class="absolute inset-0 flex snap-x snap-mandatory overflow-x-auto scroll-smooth
                [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @forelse ($illustrazioni as $i => $illustrazione)
            <div id="benvenuto-{{ $i + 1 }}" class="h-full w-full shrink-0 snap-center snap-always">
{{-- Illustrazione decorativa: alt intenzionalmente vuoto. --}}
                <img src="{{ asset('images/prelogin/'.$illustrazione) }}" alt=""
                     class="h-full w-full object-cover">
            </div>
        @empty
            <div class="h-full w-full shrink-0 bg-primary"></div>
        @endforelse
    </div>

{{-- Il contenuto sta sopra l'immagine; lo scrim scuro ne garantisce la leggibilità.
     pointer-events-none lascia passare lo swipe all'immagine, tranne dove serve. --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 flex justify-center
                bg-gradient-to-t from-black/85 via-black/45 to-transparent px-6 pb-10 pt-40">
        <div class="pointer-events-auto w-full max-w-md text-center">
        <div class="flex items-center justify-center gap-3">
            @if (file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="" class="h-12 w-12 rounded-card object-cover">
            @else
                <span class="flex h-12 w-12 items-center justify-center rounded-card bg-primary text-sm font-bold text-on-primary">D&D</span>
            @endif

            <h1 class="font-display text-3xl font-normal text-white">{{ config('app.name') }}</h1>
        </div>

        <p class="mt-5 text-sm leading-relaxed text-white/80">
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
                       class="h-2.5 w-2.5 rounded-full bg-white/40 transition
                              aria-[current]:w-6 aria-[current]:bg-active"></a>
                @endforeach
            </nav>
        @endif

            <x-button variant="secondary" size="lg" full class="mt-8" :href="route('login')">
                Tiriamo i Dadi
            </x-button>
        </div>
    </div>
</div>

</body>
</html>
