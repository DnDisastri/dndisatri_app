{{-- Partial condiviso dai gruppi di capacità del Turno; `$gruppo` può essere null. --}}
@if (filled($gruppo))
    @if ($introduzione)
        <p class="text-sm text-muted">{{ $introduzione }}</p>
    @endif

    <ul class="mt-1 space-y-3">
        @foreach ($gruppo as $capacità)
            <li>
                <p class="flex flex-wrap items-baseline gap-x-2">
                    <span class="font-medium text-fg">{{ $capacità['nome'] }}</span>

                    <span class="text-xs text-muted">
                        {{ $capacità['origine'] }}, {{ $capacità['livello'] }}º livello
                    </span>

                    @if ($capacità['mio'])
                        <span class="text-xs text-muted"
                              title="Nome tradotto da noi: sul manuale può essere scritto diversamente">·  nome nostro</span>
                    @endif
                </p>

                <p class="text-sm text-muted">{{ $capacità['testo'] }}</p>

                @if ($capacità['usi'])
                    <p class="text-xs text-on-accent-soft">{{ $capacità['usi'] }}</p>
                @endif
                
                @if ($capacità['controllare'])
                    <p class="text-xs text-on-danger-soft">
                        Da ricontrollare sul manuale: questo riassunto è scritto a memoria.
                    </p>
                @endif
            </li>
        @endforeach
    </ul>
@endif
