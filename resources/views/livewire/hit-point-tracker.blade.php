@php
    use App\Domain\Dnd\Ability;

    $tile = 'rounded-card border border-line bg-surface px-4 py-3 text-center';

    // Barra fra 0 e 100: i PF negativi si tengono, la barra non va indietro.
    $pct = $max > 0 ? max(0, min(100, (int) round($character->hp_current / $max * 100))) : 0;
@endphp

<div>
    <div class="rounded-card border border-line bg-surface px-4 py-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Punti ferita</p>
                <p class="flex items-baseline gap-1.5">
                    <span class="text-4xl font-bold leading-none {{ $character->hp_current <= 0 ? 'text-on-danger-soft' : 'text-fg' }}">
                        {{ $character->hp_current }}
                    </span>
                    <span class="text-muted">/ {{ $max }} PF</span>
                </p>
            </div>

            @if ($character->hp_temp > 0)
                <span class="shrink-0 rounded-full bg-accent-soft px-2 py-0.5 text-sm font-semibold text-primary">
                    +{{ $character->hp_temp }} temp.
                </span>
            @endif
        </div>

        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-page">
            <div class="h-full rounded-full bg-active transition-all" style="width: {{ $pct }}%"></div>
        </div>

        @if ($character->hp_current <= 0)
            {{-- Sotto zero non è un errore: si tiene per vedere quanto in
                 profondità si è andati. --}}
            <p class="mt-1 text-sm text-on-danger-soft">a terra</p>
        @endif
    </div>

    {{-- Calcolati, mai letti da una colonna. Due o quattro per riga in base al contenitore. --}}
    <div class="@container mt-3">
    {{-- Iniziativa e competenza in coppia: su due colonne finiscono sulla stessa riga. --}}
    <div class="grid grid-cols-2 gap-2 @md:grid-cols-4">
        {{-- La «m» della velocità sta sulla sigla: sul numero «7,5 m» andrebbe a capo. --}}
        @foreach ([
            [App\Enums\Icon::ArmorClass, $character->armorClass(), 'CA'],
            [App\Enums\Icon::Speed, rtrim(rtrim(number_format($character->speed, 1, ',', ''), '0'), ','), 'm · Vel.'],
            [App\Enums\Icon::Initiative, Ability::format($character->initiative()), 'Iniz.'],
            [App\Enums\Icon::ProficiencyBonus, Ability::format($character->proficiencyBonus()), 'Comp.'],
        ] as [$icona, $valore, $sigla])
            <div class="{{ $tile }}">
                <span class="flex items-center justify-center gap-1.5 text-lg font-bold text-fg">
                    <x-icona :is="$icona" class="h-5 w-5 shrink-0 text-muted" />
                    {{ $valore }}
                </span>
                <span class="block text-xs text-muted">{{ $sigla }}</span>
            </div>
        @endforeach
    </div>
    </div>

    @error('pf')
        <p class="mt-2 text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror

    @if ($canManage)
        {{-- Tiri contro morte, solo da terra: lo stesso dato lo corregge il DM dal tracker. --}}
        @if ($character->isDying())
            <div class="mt-3 mb-4 rounded-card border border-line bg-surface px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Tiri contro morte</p>

                <div class="mt-2 space-y-2">
                    @foreach (['successo' => ['Successi', 'death_save_successes', 'bg-primary border-primary', 'hover:border-primary'],
                               'fallimento' => ['Fallimenti', 'death_save_failures', 'bg-active border-active', 'hover:border-active']] as $tipo => [$etichetta, $campo, $pieno, $vuotoHover])
                        <div class="flex items-center gap-2">
                            <span class="w-24 text-sm text-muted">{{ $etichetta }}</span>
                            @for ($n = 1; $n <= 3; $n++)
                                <button type="button" wire:click="tiroMorte('{{ $tipo }}', {{ $n }})"
                                        aria-label="{{ $etichetta }} {{ $n }}"
                                        @class([
                                            'h-6 w-6 rounded-full border-2 transition',
                                            $pieno => $character->{$campo} >= $n,
                                            'border-line '.$vuotoHover => $character->{$campo} < $n,
                                        ])></button>
                            @endfor
                        </div>
                    @endforeach
                </div>

                @if ($character->death_save_successes >= 3)
                    <p class="mt-2 text-sm text-muted">Stabile: incosciente, ma fuori pericolo.</p>
                @elseif ($character->death_save_failures >= 3)
                    <p class="mt-2 text-sm text-on-danger-soft">Tre fallimenti: la fine è nelle mani del dungeon master.</p>
                @endif
            </div>
        @endif

        {{-- Un interruttore sceglie danni o cure e «Applica» esegue. --}}
        <div class="mt-3 flex gap-1 rounded-full border border-line bg-surface mb-4" role="group" aria-label="Danni o cure">
            <button type="button" wire:click="$set('modo', 'danni')" aria-pressed="{{ $modo === 'danni' ? 'true' : 'false' }}"
                    @class([
                        'flex-1 rounded-full px-3 py-2 text-sm font-semibold transition',
                        'bg-active text-on-active' => $modo === 'danni',
                        'text-muted hover:text-fg' => $modo !== 'danni',
                    ])>Danni</button>
            <button type="button" wire:click="$set('modo', 'cure')" aria-pressed="{{ $modo === 'cure' ? 'true' : 'false' }}"
                    @class([
                        'flex-1 rounded-full px-3 py-2 text-sm font-semibold transition',
                        'bg-primary text-on-primary' => $modo === 'cure',
                        'text-muted hover:text-fg' => $modo !== 'cure',
                    ])>Cure</button>
        </div>

        <div class="mt-2 flex gap-2">
            <input type="number" wire:model="amount" min="1" aria-label="Quanti punti ferita"
                   class="w-24 shrink-0 rounded-md border border-line bg-page px-2 py-2 text-center text-sm text-fg">
            <x-button full type="button" wire:click="applica"
                      :variant="$modo === 'cure' ? 'secondary' : 'primary'">Applica</x-button>
        </div>

        {{-- I temporanei non si sommano ai PF: casella a parte. --}}
        <div class="mt-2 text-center">
            <button type="button" wire:click="$toggle('mostraTemp')"
                    class="text-sm transition hover:text-fg">
                + Aggiungi punti ferita temporanei
            </button>
        </div>

        @if ($mostraTemp)
            <div class="mt-2 flex gap-2">
                <input type="number" wire:model="tempAmount" min="1" aria-label="Quanti temporanei"
                       class="w-24 shrink-0 rounded-md border border-line bg-page px-2 py-2 text-center text-sm text-fg">
                <x-button full type="button" variant="quiet" wire:click="aggiungiTemporanei"
                          title="Non si sommano: vince il valore più alto">Aggiungi temporanei</x-button>
            </div>
        @endif

        <div class="mt-3 overflow-hidden rounded-lg border border-line bg-surface">
            <button type="button" wire:click="$toggle('mostraRiposo')"
                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left transition hover:bg-page">
                <span>
                    <span class="block text-sm font-semibold text-fg">Riposo e dadi vita</span>
                    <span class="block text-xs {{ $hitDiceLeft === 0 ? 'text-on-danger-soft' : 'text-muted' }}">
                        {{ $hitDiceLeft }}/{{ $hitDiceTotal }} dadi disponibili
                    </span>
                </span>
                <x-icona :is="\App\Enums\Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition {{ $mostraRiposo ? 'rotate-180' : '' }}" />
            </button>

            @if ($mostraRiposo)
                <div class="space-y-2 border-t border-line p-3">
                    {{-- Apre il riquadro dove si scrive il tiro: non spende finché non si conferma. --}}
                    <x-button size="sm" full variant="quiet" type="button" wire:click="chiediDadoVita"
                              :disabled="$hitDiceLeft === 0">
                        Spendi un dado vita
                    </x-button>

                    {{-- Il riposo breve c'è per tutti: è quando si spendono i dadi vita. --}}
                    @foreach ([App\Enums\RestType::Long, App\Enums\RestType::Short] as $tipo)
                        <x-button size="sm" full variant="quiet" type="button"
                                  wire:click="chiediRiposo('{{ $tipo->value }}')"
                                  title="{{ $tipo->description() }}">
                            {{ $tipo->label() }}
                        </x-button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Fuori dalla tendina: un'azione senza numeri visibili sembrerebbe un pulsante rotto. --}}
        @if ($riposo)
            <p class="mt-2 text-center text-xs text-muted">{{ $riposo }}</p>
        @endif
    @endif

    {{-- Un dado vita si spende nel riposo breve (si scrive il tiro fatto col dado vero)
         oppure consumato da un privilegio di classe. --}}
    @if ($modaleDado)
        <x-modal title="Spendi un dado vita" close="annullaDadoVita">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">
                    Te ne restano <span class="font-bold text-fg">{{ $hitDiceLeft }}</span>
                    su {{ $hitDiceTotal }}, da d{{ $character->hit_die }}.
                </p>

                @error('pf')
                    <p class="text-sm text-on-danger-soft">{{ $message }}</p>
                @enderror

                <div>
                    <label for="dado-vita" class="block text-muted">
                        Quanto hai fatto col d{{ $character->hit_die }}?
                    </label>
                    <input id="dado-vita" type="number" wire:model="dadoVita"
                           min="1" max="{{ $character->hit_die }}" autofocus
                           class="mt-1 w-full rounded-md border border-line bg-page px-2 py-2
                                  text-center text-lg text-fg">
                    <p class="mt-1 text-xs text-muted">
                        Il modificatore di Costituzione lo aggiungiamo noi.
                    </p>
                </div>

                <x-button full type="button" wire:click="spendHitDie">Recupera</x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="spendiDadoSenzaCura"
                            class="text-sm text-muted transition hover:text-fg">
                        Spendilo e basta, senza curare
                    </button>
                    <p class="text-xs text-muted">Per i privilegi di classe che consumano un dado vita.</p>
                </div>
            </div>
        </x-modal>
    @endif

    {{-- Un riposo cancella lo stato della sessione e non si annulla:
         la conferma dice cosa torna indietro, con i numeri attuali. --}}
    @if ($conferma)
        @php $tipo = App\Enums\RestType::from($conferma); @endphp

        <x-modal :title="$tipo->label()" close="annullaRiposo">
            <div class="space-y-3 text-left text-sm">
                <p class="text-muted">Stai per recuperare:</p>

                <ul class="space-y-1">
                    @foreach ($this->recuperi($tipo) as $voce)
                        <li class="flex gap-2 text-fg">
                            <span class="text-muted">·</span> {{ $voce }}
                        </li>
                    @endforeach
                </ul>

                <x-button full type="button" wire:click="rest('{{ $tipo->value }}')">
                    {{ $tipo->label() }}
                </x-button>

                <div class="border-t border-line pt-3">
                    <button type="button" wire:click="annullaRiposo"
                            class="text-sm text-muted transition hover:text-fg">
                        Non adesso
                    </button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
