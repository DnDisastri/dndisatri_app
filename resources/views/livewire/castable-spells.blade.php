@php
    use App\Enums\Icon;
    use App\Livewire\SpellSlotTracker;

    // Le due riserve, come nel tracker: prima i normali, poi il patto.
    $blocchi = collect([$standard, $pact])->reject->isEmpty();

    $etichette = ['colpo' => 'Tiri tu per colpire', 'salvezza' => 'Tiro salvezza del bersaglio', 'utilita' => 'Nessun tiro'];
    $iconeGruppo = ['colpo' => Icon::SpellAttack, 'salvezza' => Icon::SpellSave, 'utilita' => Icon::NoRoll];
@endphp

{{-- Un unico contenitore: il bordo e gli angoli stanno qui, sul riquadro, non
     sul bottone. Prima il bottone aveva angoli tondi suoi e il corpo un secondo
     bordo sotto: si vedeva la cucitura, e da aperto sembravano due scatole
     sovrapposte. --}}
<div class="overflow-hidden rounded-xl border border-line bg-surface">
    {{-- L'apertura è uno stato del componente, così spendere uno slot (che
         ridisegna l'isola) non richiude la tendina. --}}
    <button type="button" wire:click="$toggle('aperto')"
            class="flex w-full items-center justify-between gap-2 px-3 py-3
                   text-left text-sm font-semibold text-fg transition hover:bg-page">
        <span class="flex items-center gap-2">
            <x-icona :is="Icon::CastSpell" class="h-4 w-4 text-muted" />
            Lancia un incantesimo
        </span>

        {{-- Da chiuso, quanti slot liberi in tutto: il colpo d'occhio senza aprire. --}}
        <span class="flex items-center gap-2 text-xs text-muted">
            @php
                $liberi = 0;
                foreach ($blocchi as $set) {
                    foreach ($set->slots as $lvl => $tot) {
                        $liberi += max(0, $tot - (int) ($used[SpellSlotTracker::keyFor($set, $lvl)] ?? 0));
                    }
                }
            @endphp
            <span>{{ $liberi }} slot</span>
            <x-icona :is="\App\Enums\Icon::Expand" class="h-4 w-4 transition {{ $aperto ? 'rotate-180' : '' }}" />
        </span>
    </button>

    @if ($aperto)
        <div class="border-t border-line px-2 pb-3 pt-1">

            {{-- Gli slot, da vedere e da spendere. Stesso gesto della sezione
                 Magia: − usa uno slot, + lo rimette. --}}
            @foreach ($blocchi as $set)
                <div class="mb-2 px-1">
                    <p class="mb-1 text-[11px] uppercase tracking-wide text-muted">
                        Slot {{ $set->isPact ? 'da patto' : 'disponibili' }}
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($set->slots as $level => $total)
                            @php
                                $key = SpellSlotTracker::keyFor($set, $level);
                                $spent = (int) ($used[$key] ?? 0);
                                $left = max(0, $total - $spent);
                            @endphp
                            <div class="flex items-center justify-between gap-2 rounded-md border-2 border-line bg-page px-3 py-1.5">
                                <span class="min-w-0">
                                    <span class="block text-[11px] text-muted">liv. {{ $level }}</span>
                                    <span class="block text-base font-bold leading-tight {{ $left === 0 ? 'text-on-danger-soft' : 'text-fg' }}">
                                        {{ $left }}/{{ $total }}
                                    </span>
                                </span>
                                @if ($canManage)
                                    <span class="flex shrink-0 gap-1">
                                        <button type="button" wire:click="spend('{{ $key }}')" @disabled($left === 0)
                                                aria-label="Usa uno slot di livello {{ $level }}"
                                                class="h-8 w-8 rounded-md border border-line text-fg transition hover:bg-surface disabled:opacity-30">−</button>
                                        <button type="button" wire:click="recover('{{ $key }}')" @disabled($spent === 0)
                                                aria-label="Rimetti uno slot di livello {{ $level }}"
                                                class="h-8 w-8 rounded-md border border-line text-fg transition hover:bg-surface disabled:opacity-30">+</button>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @error('slot') <p class="px-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror

            {{-- Gli incantesimi castabili, divisi per come colpiscono e ordinati
                 per livello. Solo colonne: le descrizioni stanno in «Magia». --}}
            @forelse ($gruppi as $chiave => $righe)
                <div class="mt-3">
                    <p class="flex items-center gap-1.5 px-1 pb-1 text-[11px] font-bold uppercase tracking-wide
                              {{ $chiave === 'colpo' ? 'text-on-danger-soft' : ($chiave === 'salvezza' ? 'text-primary' : 'text-muted') }}">
                        <x-icona :is="$iconeGruppo[$chiave]" class="h-3.5 w-3.5" />
                        {{ $etichette[$chiave] }}
                    </p>

                    {{-- Riga compatta, come i trucchetti: nome + livello + spia a
                         sinistra, il numero (colpire o CD) e «Lancia» a destra, e
                         sotto in linea tempo · gittata · danni. --}}
                    @foreach ($righe as $r)
                        <div class="border-t border-line px-1 py-2">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                        <span class="rounded bg-page px-1.5 py-0.5 text-[10px] font-bold text-muted">{{ $r['livello'] }}°</span>
                                        <span class="text-sm font-semibold text-fg">{{ $r['nome'] }}</span>
                                        @if ($r['su'])
                                            {{-- La spia del potenziamento, evidente: una pastiglia
                                                 accento, non un trattino grigio che si perde. `ml-1`
                                                 la stacca dal nome, o gli resta appiccicata. --}}
                                            <span title="Si potenzia in uno slot di livello più alto"
                                                  class="ml-1 inline-flex items-center rounded bg-accent-soft px-1 py-0.5 text-on-accent-soft">
                                                <x-icona :is="Icon::Upcast" class="h-3.5 w-3.5" />
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block text-xs text-muted">
                                        {{ $r['tempo'] }}@if ($r['gittata']) · {{ $r['gittata'] }}@endif @if ($r['danni'])· {{ $r['danni'] }}@endif
                                    </span>
                                </span>

                                <span class="flex shrink-0 items-center gap-2">
                                    @if ($r['roll'])
                                        <span class="whitespace-nowrap text-sm font-bold text-fg">{{ $r['roll'] }}</span>
                                    @endif
                                    @if ($canManage)
                                        <button type="button"
                                                wire:click="cast({{ $r['id'] }}, {{ $r['livello'] }}, {{ $r['su'] ? 'true' : 'false' }})"
                                                class="rounded-md border border-line px-2.5 py-1 text-xs font-semibold text-fg transition hover:border-active">
                                            Lancia
                                        </button>
                                    @endif
                                </span>
                            </div>

                            {{-- Se questo è l'incantesimo in scelta, la domanda: con che
                                 slot lanciarlo. Compare solo per chi si potenzia e ha più
                                 livelli liberi — altrimenti «Lancia» spende e basta. --}}
                            @if ($scelta === $r['id'])
                                <div class="mt-2 rounded-md bg-page p-2">
                                    <p class="mb-1.5 text-xs text-muted">Con che slot lo lanci?</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($opzioni as $o)
                                            @if ($o['livello'] >= $r['livello'])
                                                <button type="button" wire:click="castAt('{{ $o['key'] }}')"
                                                        class="inline-flex items-center gap-1 rounded-md border border-line bg-surface
                                                               px-3 py-1.5 text-xs font-semibold text-fg transition hover:border-active">
                                                    {{ $o['key'] === 'pact' ? 'Patto' : $o['livello'].'°' }}
                                                    @if ($o['livello'] > $r['livello'])
                                                        <x-icona :is="Icon::Upcast" class="h-3 w-3 text-on-accent-soft" />
                                                    @endif
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="mt-3 px-2 text-sm text-muted">Non hai slot per lanciare nessun incantesimo, ora.</p>
            @endforelse

            @if ($nascosti > 0)
                <p class="mt-2 px-2 text-xs italic text-muted">
                    Nascosti {{ $nascosti }}: niente slot per lanciarli.
                </p>
            @endif
        </div>
    @endif
</div>
