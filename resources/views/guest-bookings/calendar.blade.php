@extends('layouts.guest')
@section('title', 'Le sessioni del mese')
@section('largo', true)

@section('content')
    <nav class="mb-4 flex items-center justify-between gap-3 text-sm" aria-label="Mesi">
        @if ($mesePrima)
            <a href="{{ route('guest-bookings.calendar', ['mese' => $mesePrima->format('Y-m')]) }}"
               class="font-semibold text-muted hover:text-fg">‹ {{ $mesePrima->translatedFormat('F') }}</a>
        @else
            <span></span>
        @endif

        <span class="text-lg font-semibold text-fg">{{ ucfirst($mese->translatedFormat('F Y')) }}</span>

        <a href="{{ route('guest-bookings.calendar', ['mese' => $meseDopo->format('Y-m')]) }}"
           class="font-semibold text-muted hover:text-fg">{{ $meseDopo->translatedFormat('F') }} ›</a>
    </nav>

    @if (session('inviata'))
        <x-note>
            Controlla la tua email: ti abbiamo mandato un link per confermare le richieste.
            Senza quel clic il dungeon master non le vede.
        </x-note>
    @elseif ($perGiorno->isEmpty())
        <x-empty>Nessuna sessione da giocare in questo mese.</x-empty>
    @else
        @if (session('error'))
            <x-note tone="danger" class="mb-4">{{ session('error') }}</x-note>
        @endif

        <p class="mb-4 max-w-2xl text-sm text-muted">
            Scegli le sessioni a cui vorresti giocare, una al giorno: se c'è posto per te, ti arriverà un'email per confermarlo.
            Non serve un account.
        </p>

        <form method="POST" action="{{ route('guest-bookings.calendar.store', ['mese' => $mese->format('Y-m')]) }}"
              data-una-al-giorno>
            @csrf

            {{-- Un riquadro per giorno: dentro si sceglie una sessione sola, e 20 sessioni restano poche righe. --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($perGiorno as $giorno => $sessioni)
                    @php $data = \Illuminate\Support\Carbon::parse($giorno); @endphp

                    <fieldset class="rounded-card border border-line bg-surface p-3">
                        <legend class="sr-only">{{ $data->translatedFormat('l j F') }}</legend>

                        <div class="mb-2 flex items-baseline justify-between gap-2">
                            <p class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-fg">{{ $data->day }}</span>
                                <span class="text-sm capitalize text-muted">{{ $data->translatedFormat('l') }}</span>
                            </p>
                            @if ($sessioni->count() > 1)
                                <span class="text-xs text-muted">scegline una</span>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @foreach ($sessioni as $sessione)
                                <label class="flex cursor-pointer items-start gap-3 rounded-[2px] bg-page px-3 py-2 ring-1 ring-transparent transition has-[:checked]:ring-active">
                                    <input type="checkbox" name="sessioni[]" value="{{ $sessione->id }}" data-giorno="{{ $giorno }}"
                                           @checked(in_array($sessione->id, old('sessioni', [])))
                                           class="mt-1 accent-active">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-fg">
                                            {{ $sessione->played_at->format('H:i') }} · {{ $sessione->campaign?->title }}
                                        </span>
                                        <span class="block truncate text-xs text-muted">{{ $sessione->displayTitle() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>

            @error('sessioni') <p class="mt-2 text-sm text-on-danger-soft">{{ $message }}</p> @enderror

            {{-- I dati una volta sola, per tutte le sessioni scelte. --}}
            <div class="mx-auto mt-6 max-w-2xl border-t border-line pt-6">
                <p class="mb-4 font-semibold text-fg">I tuoi dati</p>
                <div class="flex flex-col gap-4">
                    @include('guest-bookings.partials.campi-ospite')

                    <x-button size="lg">Chiedo un posto</x-button>
                </div>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Hai un account? <a href="{{ route('login') }}" class="font-semibold text-fg hover:underline">Entra</a> e chiedi il posto col tuo personaggio.
        </p>
    @endif
@endsection
