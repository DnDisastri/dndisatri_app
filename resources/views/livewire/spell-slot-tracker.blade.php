@php
    use App\Livewire\SpellSlotTracker;

    /*
     * Le due riserve, nell'ordine in cui hanno senso: prima i normali, poi il
     * patto. Chi ne ha una sola vede una sola; un Warlock multiclasse le vede
     * tutte e due, con l'etichetta che dice quale.
     */
    $blocchi = collect([$standard, $pact])->reject->isEmpty();
@endphp

<div class="space-y-4">
    @foreach ($blocchi as $set)
        <div>
            <p class="mb-1 text-xs uppercase tracking-wide text-muted">
                Slot {{ $set->isPact ? 'da patto' : 'disponibili' }}
                @if ($set->isPact)
                    <span class="text-muted">(tornano anche col riposo breve)</span>
                @endif
            </p>

            {{-- Due colonne, metà ciascuna. Erano riquadri stretti che si
                 accodavano dove capitava, e con tre livelli finivano due sopra e
                 uno sotto, largo la metà: una griglia li tiene in fila e dà a ogni
                 livello lo stesso peso, che è quello che sono.

                 Sono più grandi perché sono la cosa che si preme durante il turno,
                 col telefono in una mano sola: due bersagli da 6 millimetri
                 accanto a un numero piccolo si sbagliano. --}}
            <div class="grid grid-cols-2 gap-2">
                @foreach ($set->slots as $level => $total)
                    @php
                        $key = SpellSlotTracker::keyFor($set, $level);
                        $spent = (int) ($used[$key] ?? 0);
                        $left = max(0, $total - $spent);
                    @endphp

                    <div class="flex items-center justify-between gap-2 rounded-md border-2 border-line bg-surface px-3 py-2">
                        <span class="min-w-0">
                            <span class="block text-xs text-muted">liv. {{ $level }}</span>
                            <span class="block text-lg font-bold leading-tight {{ $left === 0 ? 'text-on-danger-soft' : 'text-fg' }}">
                                {{ $left }}/{{ $total }}
                            </span>
                        </span>

                        @if ($canManage)
                            <span class="flex shrink-0 gap-1">
                                <button type="button" wire:click="spend('{{ $key }}')" @disabled($left === 0)
                                        title="Usa uno slot" aria-label="Usa uno slot di livello {{ $level }}"
                                        class="h-9 w-9 rounded-md border border-line text-base text-fg
                                               transition hover:bg-page disabled:opacity-30">−</button>
                                <button type="button" wire:click="recover('{{ $key }}')" @disabled($spent === 0)
                                        title="Rimetti a posto uno slot" aria-label="Rimetti uno slot di livello {{ $level }}"
                                        class="h-9 w-9 rounded-md border border-line text-base text-fg
                                               transition hover:bg-page disabled:opacity-30">+</button>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @error('slot')
        <p class="text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror

    {{-- I riposi non stanno più qui ma nell'intestazione, accanto ai punti
         ferita: il lungo li rimette al massimo, e questa sezione per chi non
         lancia non esiste nemmeno. --}}
</div>
