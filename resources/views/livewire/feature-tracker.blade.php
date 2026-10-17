@php
    use App\Domain\Dnd\Features;
    use App\Enums\Icon;
    use App\Enums\RestType;
@endphp

<div>
    @if ($contatori->isNotEmpty())
        {{-- Gli effetti accesi in cima: sono quello che cambia i numeri adesso. --}}
        @foreach ($attivi as $attivo)
            <x-note tone="info" class="mb-3">
                <span class="flex items-start justify-between gap-3">
                    <span>
                        <strong>{{ $attivo['nome'] }} attiva.</strong>
                        @if ($attivo['effetto']) {{ $attivo['effetto'] }} @endif
                    </span>
                    @if ($puoGestire)
                        <button type="button" wire:click="attiva({{ $attivo['id'] }})"
                                class="shrink-0 text-sm font-semibold underline">Termina</button>
                    @endif
                </span>
            </x-note>
        @endforeach

        <x-panel title="Risorse" :icon="Icon::Privileges" class="mb-4">
            @error('usi')
                <x-note tone="danger" class="mb-3">{{ $message }}</x-note>
            @enderror

            <ul class="divide-y divide-line">
                @foreach ($contatori as $p)
                    @php
                        $senzaLimite = $p['massimo'] === Features::UNLIMITED;
                        $restano = $p['massimo'] === null || $senzaLimite ? null : max(0, $p['massimo'] - $p['usati']);
                        $ritorno = match (true) {
                            $p['ritorno'] === RestType::Short => 'tornano col riposo breve',
                            $p['ritorno'] === RestType::Long && $p['privilegio']->short_rest_from_level !== null && $p['livelloClasse'] >= $p['privilegio']->short_rest_from_level => 'tornano col riposo breve',
                            $p['ritorno'] === RestType::Long => 'tornano col riposo lungo',
                            default => null,
                        };
                    @endphp

                    <li class="py-3 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                            <div class="min-w-0">
                                <p class="font-medium text-fg">{{ $p['nome'] }}</p>
                                @if ($ritorno || $senzaLimite)
                                    <p class="text-xs text-muted">{{ $senzaLimite ? 'senza limite' : $ritorno }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($restano !== null)
                                    <span class="text-sm" aria-label="{{ $restano }} su {{ $p['massimo'] }}">
                                        <span @class(['font-bold', 'text-fg' => $restano > 0, 'text-on-danger-soft' => $restano === 0])>{{ $restano }}</span>
                                        <span class="text-muted">/ {{ $p['massimo'] }}</span>
                                    </span>
                                @endif

                                @if ($puoGestire)
                                    @if ($p['attivabile'])
                                        <x-button size="sm" :variant="$p['attivo'] ? 'quiet' : 'primary'" type="button"
                                                  wire:click="attiva({{ $p['id'] }})" :disabled="! $p['attivo'] && $restano === 0">
                                            {{ $p['attivo'] ? 'Termina' : 'Attiva' }}
                                        </x-button>
                                    @elseif ($restano !== null)
                                        <x-button size="sm" type="button" wire:click="usa({{ $p['id'] }})" :disabled="$restano === 0">
                                            Usa
                                        </x-button>
                                    @endif

                                    @if ($restano !== null && $p['usati'] > 0)
                                        <button type="button" wire:click="rimetti({{ $p['id'] }})"
                                                class="text-xs text-muted underline transition hover:text-fg"
                                                title="Rimetti un uso">+1</button>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Chi consuma questa riserva: un tocco spende i suoi punti. --}}
                        @if ($puoGestire && $spese->has($p['nome']))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($spese->get($p['nome']) as $spesa)
                                    <button type="button" wire:click="usa({{ $spesa['id'] }})"
                                            @disabled($restano !== null && $restano < $spesa['quanti'])
                                            class="rounded-full border border-line px-2.5 py-1 text-xs text-fg transition hover:border-active disabled:cursor-not-allowed disabled:opacity-50">
                                        {{ $spesa['nome'] }} <span class="text-muted">· {{ $spesa['quanti'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif
</div>
