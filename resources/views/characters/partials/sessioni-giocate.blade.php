{{-- Le sessioni giocate dal personaggio, in griglia; la nota del giocatore si apre in un modale. --}}
@php
    $scrive = auth()->user()->can('writeJournal', $character);
@endphp

<section id="sessioni" class="mb-2 scroll-mt-20">
    <h3 class="mb-1 text-lg font-semibold text-fg">Sessioni giocate</h3>
    <p class="mb-3 text-sm text-muted">
        {{ $sessioni->total() === 1 ? '1 sessione' : $sessioni->total().' sessioni' }}.
        Le note le leggono solo {{ $scrive ? 'tu' : 'il giocatore' }}, i dungeon master e gli admin.
    </p>

    {{-- Solo le campagne giocate: le pill sono già un primo filtro. Gli altri filtri dell'indirizzo restano. --}}
    @if ($campagne->count() > 1)
        @php
            $pill = 'shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold transition';
            $link = fn (?string $slug) => route('characters.ledger', [$character, ...request()->except(['campagna', 'sessioni']), ...array_filter(['campagna' => $slug])]).'#sessioni';
        @endphp
        <nav class="mb-3 flex flex-wrap gap-2" aria-label="Filtra per campagna">
            <a href="{{ $link(null) }}" @if (! $campagna) aria-current="true" @endif
               @class([$pill, 'bg-active text-on-active' => ! $campagna, 'border border-line text-fg hover:border-active' => $campagna])>Tutte le campagne</a>
            @foreach ($campagne as $c)
                <a href="{{ $link($c->slug) }}" @if ($campagna?->is($c)) aria-current="true" @endif
                   @class([$pill, 'bg-active text-on-active' => $campagna?->is($c), 'border border-line text-fg hover:border-active' => ! $campagna?->is($c)])>{{ $c->title }}</a>
            @endforeach
        </nav>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($sessioni as $sessione)
            @php
                $nota = $diario->get($sessione->id)?->body;
                $tavolo = $tavoli[$sessione->pivot->session_table_id] ?? null;
                $dialogo = 'nota-'.$sessione->id;
            @endphp

            <button type="button" data-apri-dialogo="{{ $dialogo }}"
                    class="flex aspect-square flex-col rounded-card border border-line bg-surface p-3 text-left transition hover:border-active">
                <span class="text-xs text-muted">{{ $sessione->played_at->translatedFormat('j M Y') }}</span>
                <span class="mt-1 line-clamp-3 font-semibold leading-tight text-fg">{{ $sessione->displayTitle() }}</span>
                <span class="mt-1 truncate text-xs text-muted">
                    {{ $sessione->campaign?->title }}@if ($tavolo) · {{ $tavolo }}@endif
                </span>

                <span class="mt-auto pt-2 text-xs">
                    @if (filled($nota))
                        <span class="line-clamp-2 text-fg">{{ $nota }}</span>
                    @else
                        <span class="font-semibold text-muted">{{ $scrive ? '+ Scrivi una nota' : 'Nessuna nota' }}</span>
                    @endif
                </span>
            </button>

            @include('characters.partials.nota-dialogo', ['nota' => $nota, 'daRegistro' => true])
        @endforeach
    </div>

    <x-paginazione :pagine="$sessioni" etichetta="Pagine delle sessioni giocate" />
</section>
