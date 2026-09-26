@if (filled($character->background))
    <x-panel title="Background">
        <p class="text-sm text-fg">{{ $character->background }}</p>
    </x-panel>
@endif

@if (filled($character->story))
    <x-panel title="La storia">
    {{-- Escape prima di `nl2br()` per mantenere gli a capo senza consentire HTML arbitrario. --}}
        <p class="text-sm text-fg">{!! nl2br(e($character->story)) !!}</p>
        <p class="text-sm text-fg">{!! nl2br(e($character->story)) !!}</p>
    </x-panel>
@endif

@if ($completa)
    @if ($character->feats->isNotEmpty())
        <x-panel title="Talenti" :icon="\App\Enums\Icon::Talents">
            <ul class="space-y-2 text-sm">
                @foreach ($character->feats as $feat)
                    <li>
                        <span class="font-semibold">{{ $feat->name }}</span>
                        @if ($feat->level)
                            <span class="text-muted">(liv. {{ $feat->level }})</span>
                        @endif
                        @if ($feat->description)
                            <p class="text-muted">{{ $feat->description }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif

    @php
        $testi = collect([
            'Tratti di specie' => $character->species_traits,
            'Privilegi di classe' => $character->class_features,
            'Privilegi di sottoclasse' => $character->subclass_features,
            'Note' => $character->notes,
        ])->filter(fn ($testo) => filled($testo));
    @endphp

    @foreach ($testi as $label => $text)
        <x-panel :title="$label">
            <p class="text-sm text-muted">{!! nl2br(e($text)) !!}</p>
        </x-panel>
    @endforeach

    {{-- Le capacità con `da_turno: false` vengono mostrate qui invece che nel riepilogo di combattimento. --}}
    @php $fuoriTurno = \App\Domain\Dnd\Features::fuoriDalTurno($character); @endphp
    @if ($fuoriTurno->isNotEmpty())
        <x-panel title="Altri privilegi">
            <ul class="space-y-3">
                @foreach ($fuoriTurno as $privilegio)
                    <li>
                        <p class="flex flex-wrap items-baseline gap-x-2">
                            <span class="font-medium text-fg">{{ $privilegio['nome'] }}</span>
                            <span class="text-xs text-muted">{{ $privilegio['origine'] }}, {{ $privilegio['livello'] }}º livello</span>
                        </p>
                        <p class="text-sm text-muted">{{ $privilegio['testo'] }}</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif
@endif

@if (! $completa)
    @if (blank($character->story))
        <x-empty>Di questo personaggio non è ancora stata scritta la storia.</x-empty>
    @endif
@elseif (blank($character->story) && $character->feats->isEmpty() && $testi->isEmpty())
    <x-empty>Di questo personaggio non è ancora stato scritto niente.</x-empty>
@endif
