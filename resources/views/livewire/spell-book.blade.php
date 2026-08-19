<div>
    {{-- Il contatore prima dell'elenco: preparare è scegliere, e scegliere
         vuol dire sapere quanti posti restano *prima* di spuntare. --}}
    @if ($prepara)
        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
            <p class="text-xs uppercase tracking-wide text-muted">Pronti per oggi</p>
            <p class="text-sm">
                <span class="font-bold {{ count($preparati) >= $limite ? 'text-on-accent-soft' : 'text-fg' }}">
                    {{ count($preparati) }}
                </span>
                <span class="text-muted">su {{ $limite }}</span>
            </p>
        </div>

        <p class="mb-2 text-xs text-muted">
            Si sceglie al mattino e vale per la giornata. I trucchetti non si preparano: ci sono sempre.
        </p>

        @error('preparazione')
            <x-note tone="danger" class="mb-3">{{ $message }}</x-note>
        @enderror
    @endif

    @forelse ($gruppi as $titolo => $spells)
        <p class="mb-1 text-xs uppercase tracking-wide text-muted">
            {{ $titolo }} <span class="text-muted">({{ $spells->count() }})</span>
        </p>

        <ul class="mb-4 divide-y divide-line overflow-hidden rounded-md border border-line">
            @foreach ($spells as $spell)
                @php
                    $testo = $spell->descriptionOrDefault();
                    $spuntabile = $prepara && $canManage && ! $spell->isCantrip();
                @endphp

                <li class="flex items-start">
                    {{-- La casella sta **fuori** dal riquadro che si apre: dentro
                         un `summary`, ogni spunta aprirebbe anche la
                         descrizione, e per preparare sei incantesimi si
                         aprirebbero sei paragrafi. --}}
                    @if ($spuntabile)
                        <label class="flex shrink-0 cursor-pointer items-center py-2 pl-3"
                               title="Tienilo pronto per oggi">
                            <input type="checkbox" value="{{ $spell->name }}" wire:model.live="preparati"
                                   aria-label="Prepara {{ $spell->name }}"
                                   class="h-5 w-5 rounded border-line accent-[var(--ui-active)]">
                        </label>
                    @endif

                    <div class="min-w-0 flex-1">
                        @if ($testo)
                            {{-- `details` fa da solo, come le altre tendine
                                 dell'applicazione: nessun javascript per aprire
                                 un paragrafo.

                                 La descrizione stava in un attributo `title`,
                                 cioè in un suggerimento che compare passandoci
                                 sopra col mouse: su un telefono non esiste il
                                 passarci sopra, e non la vedeva nessuno. --}}
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2
                                                text-sm transition hover:bg-page
                                                [&::-webkit-details-marker]:hidden">
                                    <span @class([
                                        'text-fg' => ! $prepara || $spell->isCantrip() || $spell->prepared,
                                        'text-muted' => $prepara && ! $spell->isCantrip() && ! $spell->prepared,
                                    ])>{{ $spell->name }}</span>

                                    <x-icona :is="\App\Enums\Icon::Expand"
                                             class="h-4 w-4 shrink-0 text-muted transition group-open:rotate-180" />
                                </summary>

                                <p class="px-3 pb-3 text-sm text-muted">{{ $testo }}</p>
                            </details>
                        @else
                            {{-- Senza descrizione la riga non si apre, e si vede
                                 che non si apre: una freccia che non porta da
                                 nessuna parte si prova una volta a testa. --}}
                            <p class="px-3 py-2 text-sm text-fg">{{ $spell->name }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @empty
        <x-empty>Nessun incantesimo sulla scheda.</x-empty>
    @endforelse
</div>
