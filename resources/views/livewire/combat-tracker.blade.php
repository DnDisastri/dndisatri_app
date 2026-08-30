<div>
    {{--
        Il tracker di combattimento. Ogni riga è un combattente: iniziativa
        (modificabile), PF con barra, CA, danno/cura e condizioni. Gli eroi hanno
        i PF veri della scheda; i mostri i loro, effimeri.

        Danno in rosso, cura in navy: è la stessa coppia di colori della scheda,
        dove «Danni» e «Cure» si distinguono così.
    --}}
    @php use App\Enums\Icon; use App\Enums\Condition; @endphp

    {{-- 1. Round e turno. --}}
    @php $diTurno = collect($combattenti)->firstWhere('id', $turnoId); @endphp
    <div class="flex items-center justify-between gap-3">
        <span class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-fg">
            Round <span class="font-display text-lg leading-none text-active">{{ $round }}</span>
        </span>

        <x-button type="button" wire:click="prossimo" :disabled="empty($combattenti)">
            {{ $turnoId === null ? 'Comincia' : 'Prossimo' }}
            <x-icona :is="Icon::GoTo" class="ml-1.5 h-4 w-4" />
        </x-button>
    </div>

    <p class="mt-2 text-sm text-muted">
        @if ($diTurno)
            Tocca a <span class="font-semibold text-fg">{{ $diTurno['nome'] }}</span>.
        @else
            Non ancora iniziato.
        @endif
    </p>

    {{-- 2. Comporre la fila. --}}
    <div class="mt-4 flex flex-wrap gap-2">
        <x-button size="sm" variant="quiet" type="button" wire:click="popolaDalTavolo">
            <x-icona :is="Icon::Characters" class="mr-1.5 h-4 w-4" /> Popola dal tavolo
        </x-button>
        <x-button size="sm" variant="quiet" type="button" wire:click="$toggle('mostraAggiungiMostro')">
            Aggiungi mostro
        </x-button>

        @unless (empty($combattenti))
            <button type="button" wire:click="azzera" wire:confirm="Svuoto il combattimento?"
                    class="ml-auto inline-flex items-center rounded-full border border-line px-3 py-1.5
                           text-sm font-semibold text-muted transition hover:border-active hover:text-fg">
                Azzera
            </button>
        @endunless
    </div>

    {{-- Aggiungi mostro: dal bestiario (si pesca) o al volo (si scrive). --}}
    @if ($mostraAggiungiMostro)
        <div class="mt-3 rounded-card border border-active bg-surface p-3">
            {{-- Dal bestiario. --}}
            <p class="mb-2 text-xs uppercase tracking-wide text-muted">Dal bestiario</p>
            <input type="search" wire:model.live.debounce.300ms="cercaMostro"
                   placeholder="Cerca un mostro…"
                   class="w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg">

            @if ($mostriTrovati->isNotEmpty())
                <div class="mt-2 space-y-1.5">
                    @foreach ($mostriTrovati as $m)
                        <div class="flex items-center justify-between gap-2 rounded-md border border-line bg-page px-3 py-2">
                            <span class="min-w-0 text-sm">
                                <span class="font-semibold text-fg">{{ $m->name }}</span>
                                <span class="text-xs text-muted"> · PF {{ $m->hp }} · CA {{ $m->ac }}</span>
                            </span>
                            <x-button size="sm" type="button" wire:click="aggiungiDalBestiario({{ $m->id }})">Aggiungi</x-button>
                        </div>
                    @endforeach
                </div>
            @elseif (trim($cercaMostro) !== '')
                <p class="mt-2 text-xs text-muted">Nessuno. Scrivilo al volo qui sotto e spunta «salva nel bestiario».</p>
            @endif

            {{-- Al volo. --}}
            <p class="mb-2 mt-4 border-t border-line pt-3 text-xs uppercase tracking-wide text-muted">Oppure al volo</p>
            <div class="flex flex-wrap items-end gap-2">
                <label class="min-w-[8rem] flex-1">
                    <span class="text-xs text-muted">Nome</span>
                    <input type="text" wire:model="mostroNome" wire:keydown.enter="aggiungiMostro" maxlength="60"
                           class="mt-1 w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg" placeholder="Goblin">
                </label>
                <label class="w-20">
                    <span class="text-xs text-muted">PF</span>
                    <input type="number" inputmode="numeric" wire:model="mostroHp" wire:keydown.enter="aggiungiMostro"
                           class="mt-1 w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg" placeholder="7">
                </label>
                <label class="w-20">
                    <span class="text-xs text-muted">CA</span>
                    <input type="number" inputmode="numeric" wire:model="mostroAc" wire:keydown.enter="aggiungiMostro"
                           class="mt-1 w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-fg" placeholder="15">
                </label>
                <x-button type="button" wire:click="aggiungiMostro">Aggiungi</x-button>
            </div>

            <label class="mt-2 flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" wire:model="salvaNelBestiario" class="rounded border-line accent-[var(--ui-active)]">
                Salva nel bestiario
            </label>

            @error('mostroNome') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
            @error('mostroHp') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
            @error('mostroAc') <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- 3. La fila. --}}
    <div class="mt-4 space-y-2.5">
        @forelse ($combattenti as $i => $c)
            @php
                $isPg = $c['tipo'] === 'pg';
                $pg = $isPg ? ($personaggi[$c['characterId']] ?? null) : null;

                if ($isPg && $pg) {
                    $hp = max(0, (int) $pg->hp_current);
                    $hpMax = $pg->effectiveHpMax();
                    $ca = $pg->armorClass();
                } else {
                    $hp = (int) ($c['hp'] ?? 0);
                    $hpMax = (int) ($c['hpMax'] ?? 0);
                    $ca = $c['ac'];
                }

                $frazione = $hpMax > 0 ? min(1, $hp / $hpMax) : 0;
                $aTerra = $hp <= 0;
                // Niente verde nella palette: pieno navy, a metà crema, in rosso
                // quando si mette male. Classi intere, non composte (lo scanner
                // di Tailwind cerca stringhe letterali).
                $barra = match (true) {
                    $aTerra, $frazione <= 0.33 => 'bg-active',
                    $frazione <= 0.66 => 'bg-accent',
                    default => 'bg-primary',
                };
                $mioTurno = $turnoId === $c['id'];
                // Il mostro ha uno statblock da aprire solo se ha di che riempirlo.
                $haStatblock = ! $isPg && (! empty($c['attacks']) || filled($c['traits'] ?? null) || filled($c['speed'] ?? null));
            @endphp

            <div wire:key="comb-{{ $c['id'] }}"
                 @class([
                     'rounded-card border bg-surface p-3',
                     'border-active ring-1 ring-active' => $mioTurno,
                     'border-line' => ! $mioTurno,
                     'opacity-70' => $aTerra && $isPg,
                 ])>

                {{-- Testa: iniziativa modificabile, nome, tipo, togli. --}}
                <div class="flex items-center gap-3">
                    <input type="number" inputmode="numeric" aria-label="Iniziativa di {{ $c['nome'] }}"
                           wire:model.blur="combattenti.{{ $i }}.iniziativa"
                           @class([
                               'h-11 w-11 shrink-0 rounded-lg border text-center font-display text-lg',
                               'border-active bg-active text-on-active' => $mioTurno,
                               'border-line bg-page text-fg' => ! $mioTurno,
                           ])>

                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-1.5 font-semibold leading-tight text-fg">
                            @if ($isPg && $pg)
                                <a href="{{ route('characters.show', $pg) }}" class="truncate hover:underline">{{ $c['nome'] }}</a>
                            @elseif ($haStatblock)
                                <button type="button" wire:click="apriStatblock('{{ $c['id'] }}')"
                                        class="inline-flex min-w-0 items-center gap-1 text-left hover:underline">
                                    <span class="truncate">{{ $c['nome'] }}</span>
                                    <x-icona :is="Icon::Discover" class="h-3.5 w-3.5 shrink-0 text-muted" />
                                </button>
                            @else
                                <span class="truncate">{{ $c['nome'] }}</span>
                            @endif

                            <span @class([
                                'shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold uppercase',
                                'bg-primary text-on-primary' => $isPg,
                                'bg-accent-soft text-on-accent-soft' => ! $isPg,
                            ])>{{ $isPg ? 'eroe' : 'mostro' }}</span>
                        </p>
                        <p class="truncate text-xs text-muted">
                            @if ($isPg && $pg)
                                {{ $pg->class }} · liv. {{ $pg->level }} · PF veri della scheda
                            @elseif ($isPg)
                                eroe non più al tavolo
                            @else
                                effimero
                            @endif
                        </p>
                    </div>

                    <button type="button" wire:click="rimuovi('{{ $c['id'] }}')" title="Togli"
                            class="shrink-0 rounded-full p-1.5 text-muted transition hover:text-on-danger-soft">
                        <x-icona :is="Icon::Close" class="h-4 w-4" />
                    </button>
                </div>

                {{-- PF + CA. --}}
                <div class="mt-3 flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex items-baseline justify-between text-xs">
                            @if ($aTerra)
                                <span class="font-semibold text-on-danger-soft">A terra</span>
                                <span class="text-on-danger-soft">0 / {{ $hpMax }}</span>
                            @else
                                <span class="text-muted">PF</span>
                                <span class="font-semibold text-fg">{{ $hp }} / {{ $hpMax }}</span>
                            @endif
                        </div>
                        <div class="h-2 overflow-hidden rounded-full border border-line bg-page">
                            <span class="block h-full rounded-full {{ $barra }}"
                                  style="width: {{ $aTerra ? 100 : max(4, round($frazione * 100)) }}%"></span>
                        </div>
                    </div>

                    @if ($ca !== null)
                        <span class="flex shrink-0 items-center gap-1.5 text-xs text-muted">
                            <x-icona :is="Icon::ArmorClass" class="h-4 w-4" /> CA <span class="font-semibold text-fg">{{ $ca }}</span>
                        </span>
                    @endif
                </div>

                {{-- I tiri contro morte dell'eroe a terra (tappa B): lo stesso
                     dato che il giocatore segna sulla sua scheda. --}}
                @if ($isPg && $pg && $aTerra)
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                        @foreach (['successo' => ['✓', 'death_save_successes', 'bg-primary border-primary'],
                                   'fallimento' => ['✗', 'death_save_failures', 'bg-active border-active']] as $tipo => [$segno, $campo, $pieno])
                            <span class="flex items-center gap-1.5">
                                <span class="text-muted">TS morte {{ $segno }}</span>
                                @for ($n = 1; $n <= 3; $n++)
                                    <button type="button" wire:click="tiroMorte('{{ $c['id'] }}', '{{ $tipo }}', {{ $n }})"
                                            aria-label="{{ $tipo }} {{ $n }} di {{ $c['nome'] }}"
                                            @class([
                                                'h-4 w-4 rounded-full border-2 transition',
                                                $pieno => $pg->{$campo} >= $n,
                                                'border-line' => $pg->{$campo} < $n,
                                            ])></button>
                                @endfor
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Condizioni addosso. --}}
                @if (! empty($c['condizioni']))
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($c['condizioni'] as $cond)
                            <button type="button" wire:click="condizione('{{ $c['id'] }}', '{{ $cond }}')"
                                    class="inline-flex items-center gap-1 rounded-full bg-danger-soft px-2 py-0.5
                                           text-xs font-semibold text-on-danger-soft transition hover:opacity-80">
                                {{ Condition::tryFrom($cond)?->label() ?? $cond }}
                                <x-icona :is="Icon::Close" class="h-3 w-3" />
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Danno / cura / condizione. --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <input type="number" inputmode="numeric" min="1" placeholder="—"
                           aria-label="Quanti punti per {{ $c['nome'] }}"
                           wire:model="colpo.{{ $c['id'] }}"
                           class="w-14 rounded-md border border-line bg-page px-2 py-2 text-center text-sm font-bold text-fg">

                    <button type="button" wire:click="danno('{{ $c['id'] }}')"
                            class="rounded-md bg-active px-3 py-2 text-sm font-bold text-on-active transition hover:opacity-90">
                        − Danno
                    </button>
                    <button type="button" wire:click="cura('{{ $c['id'] }}')"
                            class="rounded-md bg-primary px-3 py-2 text-sm font-bold text-on-primary transition hover:opacity-90">
                        + Cura
                    </button>

                    <button type="button" wire:click="apriCondizioni('{{ $c['id'] }}')"
                            class="ml-auto inline-flex items-center rounded-full border border-dashed border-line px-3 py-2
                                   text-sm text-muted transition hover:border-active hover:text-fg">
                        + Condizione
                    </button>
                </div>

                {{-- Il selettore delle condizioni, dalla lista fissa del manuale. --}}
                @if ($condizioniAperte === $c['id'])
                    <div class="mt-2 rounded-md border border-line bg-page p-2">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($condizioniDisponibili as $valore => $etichetta)
                                @php $attiva = in_array($valore, $c['condizioni'], true); @endphp
                                <button type="button" wire:click="condizione('{{ $c['id'] }}', '{{ $valore }}')"
                                        @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold transition',
                                            'bg-danger-soft text-on-danger-soft' => $attiva,
                                            'border border-line text-muted hover:text-fg' => ! $attiva,
                                        ])>
                                    {{ $etichetta }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-card border border-dashed border-line px-4 py-6 text-center text-sm text-muted">
                Nessun combattente. «Popola dal tavolo» mette gli eroi, poi aggiungi i mostri.
            </div>
        @endforelse
    </div>

    {{-- Lo statblock esteso del mostro, al clic sul nome. --}}
    @if ($statblock)
        <x-modal :title="$statblock['nome']" close="chiudiStatblock">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">
                    PF <span class="font-semibold text-fg">{{ $statblock['hpMax'] }}</span>
                    · CA <span class="font-semibold text-fg">{{ $statblock['ac'] }}</span>
                    @if (filled($statblock['speed'] ?? null))
                        · Vel. <span class="font-semibold text-fg">{{ $statblock['speed'] }}</span>
                    @endif
                </p>

                @if (! empty($statblock['attacks']))
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Attacchi</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($statblock['attacks'] as $a)
                                <li>
                                    <span class="font-medium text-fg">{{ $a['nome'] ?? '—' }}</span>
                                    @if (! empty($a['bonus'])) <span class="text-muted">{{ $a['bonus'] }} per colpire</span> @endif
                                    @if (! empty($a['danni'])) <span class="text-muted">· {{ $a['danni'] }} danni</span> @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled($statblock['traits'] ?? null))
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Tratti e note</p>
                        <p class="mt-1 whitespace-pre-line text-fg">{{ $statblock['traits'] }}</p>
                    </div>
                @endif
            </div>
        </x-modal>
    @endif
</div>
