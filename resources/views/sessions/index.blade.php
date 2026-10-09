@extends('layouts.app')
@section('title', 'Sessioni')

@section('content')

@php
    use App\Enums\Icon;

    $oggi = today();
    $primo = $mese->copy()->startOfMonth();

    // `dayOfWeekIso` parte da 1 per lunedì; il resto sono celle vuote prima del giorno 1.
    $vuotePrima = $primo->dayOfWeekIso - 1;
@endphp

<x-pagina class="space-y-6">

    <div>
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="Icon::Sessions" class="h-7 w-7" /> Sessioni
        </h2>
        <x-intro :page="\App\Enums\IntroPage::Sessions" class="mt-1" />
    </div>

    {{-- Il calendario pubblico del mese, da far uscire insieme al calendario delle sessioni. --}}
    @if (auth()->user()->isDm() || auth()->user()->isAdmin())
        <x-panel>
            <p class="text-xs uppercase tracking-wide text-muted">Link del calendario per chi non ha un account</p>
            <p class="mt-1 text-xs text-muted">
                Chi lo apre vede le sessioni di {{ $mese->translatedFormat('F') }} e chiede un posto a più sessioni insieme, una al giorno.
            </p>
            <input type="text" readonly value="{{ route('guest-bookings.calendar', ['mese' => $mese->format('Y-m')]) }}"
                   aria-label="Link del calendario per chi non ha un account"
                   class="mt-2 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-fg">
        </x-panel>
    @endif

    <div class="space-y-6 lg:grid lg:grid-cols-5 lg:items-start lg:gap-8 lg:space-y-0">
    <x-panel class="lg:sticky lg:top-8 lg:col-span-2">
{{-- Il mese resta nell'URL, così la vista può essere condivisa e riaperta nello stesso stato. --}}
        <div class="flex items-center justify-between gap-3">
            <x-button variant="quiet" size="sm"
                      :href="route('sessions.index', ['mese' => $mese->copy()->subMonth()->format('Y-m')])"
                      aria-label="Il mese prima">‹</x-button>

            <p class="text-lg font-semibold capitalize text-fg">{{ $mese->translatedFormat('F Y') }}</p>

            <x-button variant="quiet" size="sm"
                      :href="route('sessions.index', ['mese' => $mese->copy()->addMonth()->format('Y-m')])"
                      aria-label="Il mese dopo">›</x-button>
        </div>

        <div class="mt-4 grid grid-cols-7 gap-1 text-center">
            @foreach (['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $giorno)
                <span class="pb-1 text-xs uppercase tracking-wide text-muted">{{ $giorno }}</span>
            @endforeach

{{-- Le celle vuote servono solo all'allineamento del calendario e vengono nascoste alle tecnologie assistive. --}}
            @for ($i = 0; $i < $vuotePrima; $i++)
                <span aria-hidden="true"></span>
            @endfor

            @foreach (range(1, $mese->daysInMonth) as $numero)
                @php
                    $giorno = $primo->copy()->setDay($numero);
                    $quelGiorno = $perGiorno[$giorno->toDateString()] ?? collect();
                @endphp

                @if ($quelGiorno->isNotEmpty())
                    <a href="#g-{{ $giorno->toDateString() }}"
                       title="{{ $quelGiorno->count() }} {{ $quelGiorno->count() === 1 ? 'sessione' : 'sessioni' }}"
                       @class([
                           'flex aspect-square items-center justify-center rounded-lg text-sm font-bold transition',
                           'bg-active text-on-active hover:opacity-90',
                           'ring-2 ring-fg' => $giorno->isSameDay($oggi),
                       ])>{{ $numero }}</a>
                @else
                    <span @class([
                        'flex aspect-square items-center justify-center rounded-lg text-sm text-muted',
                        'ring-2 ring-fg font-bold text-fg' => $giorno->isSameDay($oggi),
                    ])>{{ $numero }}</span>
                @endif
            @endforeach
        </div>
    </x-panel>

    <section class="lg:col-span-3">
        <h3 class="mb-3 font-display text-lg font-normal capitalize text-fg">
            Le sessioni di {{ $mese->translatedFormat('F') }}
        </h3>

        @if ($perGiorno->isEmpty())
            <x-empty>Nessuna sessione in questo mese.</x-empty>
        @else
            {{-- Un riquadro per giorno, come nel calendario degli ospiti: si spuntano le sessioni e si chiede il posto una volta sola. --}}
            <form method="POST" action="{{ route('sessions.book-many') }}" data-una-al-giorno>
                @csrf

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($perGiorno as $giorno => $quelGiorno)
                        @php
                            $data = \Illuminate\Support\Carbon::parse($giorno);
                            $giornoPreso = in_array($giorno, $giorniPresi, true);
                        @endphp

                        <fieldset id="g-{{ $giorno }}" class="scroll-mt-20 rounded-card border border-line bg-surface p-3">
                            <legend class="sr-only">{{ $data->translatedFormat('l j F') }}</legend>

                            <div class="mb-2 flex items-baseline justify-between gap-2">
                                <p class="flex items-baseline gap-2">
                                    <span class="text-2xl font-bold text-fg">{{ $data->day }}</span>
                                    <span class="text-sm capitalize text-muted">{{ $data->translatedFormat('l') }}</span>
                                </p>
                                @if ($quelGiorno->count() > 1 && ! $giornoPreso && $data->isFuture())
                                    <span class="text-xs text-muted">scegline una</span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                @foreach ($quelGiorno as $session)
                                    @php
                                        $mio = $mieiPosti->get($session->id);
                                        $puoChiedere = ! $giornoPreso && $mieiPersonaggi->isNotEmpty() && auth()->user()->can('book', $session);
                                    @endphp

                                    <div class="flex items-start gap-3 rounded-[2px] bg-page px-3 py-2 ring-1 ring-transparent transition has-[:checked]:ring-active">
                                        @if ($puoChiedere)
                                            <input type="checkbox" name="sessioni[]" value="{{ $session->id }}" id="s-{{ $session->id }}"
                                                   data-giorno="{{ $giorno }}" @checked(in_array($session->id, old('sessioni', [])))
                                                   class="mt-1 accent-active">
                                        @endif

                                        <span class="min-w-0 flex-1">
                                            <label @if ($puoChiedere) for="s-{{ $session->id }}" @endif
                                                   @class(['block text-sm font-semibold text-fg', 'cursor-pointer' => $puoChiedere])>
                                                {{ $session->played_at->format('H:i') }} · {{ $session->campaign?->title }}
                                            </label>
                                            <a href="{{ route('sessions.show', ['session' => $session, 'da' => 'serate']) }}"
                                               class="block truncate text-xs text-muted hover:text-fg hover:underline">{{ $session->displayTitle() }} ›</a>
                                        </span>

                                        @if ($mio)
                                            <x-badge :tone="$mio->status->tone()" class="shrink-0">{{ $mio->status->label() }}</x-badge>
                                        @elseif (! $session->isUpcoming())
                                            <x-badge tone="outline" class="shrink-0">Giocata</x-badge>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>

                {{-- La barra resta a vista mentre si scorre il mese; sul telefono sopra la barra di navigazione. --}}
                @if ($mieiPersonaggi->isNotEmpty())
                    <div class="sticky bottom-24 z-20 mt-4 flex flex-wrap items-center gap-2 rounded-card border border-line bg-surface p-3 shadow-lg lg:bottom-4">
                        <label for="character_id" class="text-sm text-muted">Con</label>
                        <select name="character_id" id="character_id" required
                                class="min-w-0 flex-1 rounded-md border border-line bg-page px-3 py-2 text-sm text-fg">
                            @foreach ($mieiPersonaggi as $pg)
                                <option value="{{ $pg->id }}" @selected((int) old('character_id') === $pg->id)>{{ $pg->name }}</option>
                            @endforeach
                        </select>
                        <x-button>Chiedo un posto</x-button>
                    </div>
                    @error('sessioni') <p class="mt-2 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
                @elseif (! auth()->user()->isAdmin())
                    <p class="mt-4 text-sm text-muted">
                        Per chiedere un posto ti serve un eroe.
                        <a href="{{ route('characters.create') }}" class="font-semibold text-active hover:underline">Crealo</a>
                    </p>
                @endif
            </form>
        @endif
    </section>
    </div>

{{-- Gli eventi mostrano sempre i prossimi appuntamenti e non seguono il mese selezionato nel calendario. --}}
    @if ($events->isNotEmpty())
        <section>
            <div class="mb-3 flex items-end justify-between gap-4">
                <div>
                    <h3 class="font-display flex items-center gap-2 text-lg font-normal text-fg">
                        <x-icona :is="Icon::Events" class="h-5 w-5" /> La bacheca del bardo
                    </h3>
                    <p class="mt-1 text-sm text-muted">Ecco i prossimi eventi in programma</p>
                </div>

                <x-button variant="secondary" class="shrink-0 max-md:hidden" :href="route('events.index')">
                    Vedi tutti gli eventi
                </x-button>
            </div>

            <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 scroll-px-4
                        [scrollbar-width:none] [&::-webkit-scrollbar]:hidden
                        md:-mx-6 md:px-6 md:scroll-px-6 lg:mx-0 lg:grid lg:grid-cols-[repeat(auto-fit,minmax(16rem,1fr))] lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ($events as $event)
                    <x-poster
                        :href="route('events.show', $event)"
                        :image="$event->cover_path
                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_path)
                            : null"
                        label="Nuovo evento"
                        :meta="'il '.$event->starts_at->format('d/m/y')"
                        :title="$event->title"
                        class="w-72 shrink-0 snap-start lg:w-auto" />
                @endforeach
            </div>

            <x-button variant="secondary" size="lg" full class="mt-3 md:hidden" :href="route('events.index')">
                Vedi tutti gli eventi
            </x-button>
        </section>
    @endif

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>
@endsection
