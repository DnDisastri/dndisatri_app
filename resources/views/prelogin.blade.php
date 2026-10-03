@section('title', config('app.name').' Qui il caos vince sempre')
{{-- Landing a tutto viewport, senza layout dell'app. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.testa')
</head>
<body class="relative h-[100dvh] overflow-hidden bg-page antialiased lg:flex">

{{-- Da `lg` due metà: illustrazione in cover a sinistra, benvenuto a destra. --}}
<div class="relative h-full w-full overflow-hidden bg-primary lg:w-1/2 lg:shrink-0">
    <a href="{{ route('about') }}"
       class="pointer-events-auto absolute right-4 top-4 z-20 rounded-full bg-black/30 px-3 py-1.5 text-sm font-medium text-white backdrop-blur transition hover:bg-black/50 lg:hidden">
        Chi siamo
    </a>

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
</div>

{{-- `pointer-events-none` lascia passare lo swipe all'immagine. Da `lg` torna nel flusso. --}}
<div class="pointer-events-none absolute inset-x-0 bottom-0 flex justify-center
            bg-gradient-to-t from-black/85 via-black/45 to-transparent px-6 pb-10 pt-40
            lg:pointer-events-auto lg:static lg:flex-1 lg:items-center lg:bg-none lg:p-12">
    <div class="pointer-events-auto w-full max-w-md text-center">
        <div class="flex items-center justify-center gap-3 lg:flex-col lg:gap-5">
            @if (file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="" class="h-12 w-12 rounded-card object-cover lg:h-24 lg:w-24">
            @else
                <span class="flex h-12 w-12 items-center justify-center rounded-card bg-primary text-sm font-bold text-on-primary lg:h-24 lg:w-24 lg:text-xl">D&D</span>
            @endif

            <h1 class="font-display text-3xl font-normal text-white lg:text-5xl lg:text-fg">{{ config('app.name') }}</h1>
        </div>

        <p class="mt-5 text-sm leading-relaxed text-white/80 lg:mt-6 lg:text-base lg:text-muted">
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
                       class="h-2.5 w-2.5 rounded-full bg-white/40 transition lg:bg-line
                              aria-[current]:w-6 aria-[current]:bg-active"></a>
                @endforeach
            </nav>
        @endif

        <x-button variant="secondary" size="lg" full class="mt-8" :href="route('login')">
            Tiriamo i Dadi
        </x-button>

        <a href="{{ route('about') }}" class="mt-4 hidden text-sm text-muted transition hover:text-fg hover:underline lg:inline-block">
            Chi siamo
        </a>
    </div>
</div>

</body>
</html>
