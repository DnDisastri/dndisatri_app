@php
    use App\Domain\Dnd\Ability;
    use App\Domain\Dnd\Features;
    use App\Enums\ActionCost;
    use App\Enums\Icon;

    $capacita = Features::perCosto($character);
    $senzaPrivilegi = Features::sottoclassiSenzaPrivilegi($character);
    $attacchi = $character->attacks();
    $trucchetti = $character->casterType()->castsSpells()
        ? $character->spells->where('level', 0)->sortBy('name')
        : collect();

    $attaccoMagico = $character->spellAttackBonus();
    $cd = $character->spellSaveDc();

    // Per i trucchetti con tiro per colpire mostra il bonus; negli altri casi caratteristica e CD.
    $numeroTrucchetto = function ($t) use ($attaccoMagico, $cd) {
        return match ($t->rollKind()) {
            'attacco' => $attaccoMagico !== null ? Ability::format($attaccoMagico).' colpire' : null,
            'cd' => $cd !== null ? trim(($t->saveAbility() ?? 'CD').' '.$cd) : null,
            default => null,
        };
    };
@endphp


@php
    $sotto = 'mb-1 mt-4 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted';
@endphp

<x-panel :title="ActionCost::Action->label()" :icon="Icon::Action">

    @if ($attacchi->isNotEmpty())
        <p class="{{ $sotto }}"><x-icona :is="Icon::Attacks" class="h-3.5 w-3.5" /> Attacchi</p>
        <div>
            @foreach ($attacchi as $attacco)
                <div class="flex items-baseline justify-between gap-3 border-t border-line py-2 first:border-t-0">
                    <span class="font-medium text-fg">
                        {{ $attacco['name'] }}
                        @if ($attacco['equipped'])
                            <span class="text-xs text-muted">· in mano</span>
                        @endif
                    </span>
                    <span class="whitespace-nowrap text-sm text-muted">
                        <span class="font-bold text-fg">{{ Ability::format($attacco['attack']) }} colpire</span> · {{ $attacco['damage'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($trucchetti->isNotEmpty())
        <p class="{{ $sotto }}"><x-icona :is="Icon::Cantrips" class="h-3.5 w-3.5" /> Trucchetti </p>
        <div>
            @foreach ($trucchetti as $trucchetto)
                @php $numero = $numeroTrucchetto($trucchetto); $danni = $trucchetto->damage($character->level); @endphp
                <div class="flex items-baseline justify-between gap-3 border-t border-line py-2 first:border-t-0">
                    <span class="font-medium text-fg">{{ $trucchetto->name }}</span>
                    <span class="whitespace-nowrap text-sm text-muted">
                        @if ($numero)<span class="font-bold text-fg">{{ $numero }}</span>@endif
                        @if ($danni) · {{ $danni }}@endif
                        @if (! $numero && ! $danni)utilità@endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($capacita->has(ActionCost::Action->value))
        <p class="{{ $sotto }}"><x-icona :is="Icon::Privileges" class="h-3.5 w-3.5" /> Privilegi </p>
        @include('characters.sezioni.partials.capacita', [
            'gruppo' => $capacita->get(ActionCost::Action->value),
            'introduzione' => null,
        ])
    @endif
</x-panel>

@if (! $character->spellSlots()->isEmpty() || ! $character->pactSlots()->isEmpty())
    <livewire:castable-spells :character="$character" :key="'cast-'.$character->id" />
@endif

@if ($capacita->has(ActionCost::Bonus->value))
    <x-panel :title="ActionCost::Bonus->label()" :icon="Icon::BonusAction">
        @include('characters.sezioni.partials.capacita', [
            'gruppo' => $capacita->get(ActionCost::Bonus->value),
            'introduzione' => null,
        ])
    </x-panel>
@endif

@if ($capacita->has(ActionCost::Reaction->value))
    <x-panel :title="ActionCost::Reaction->label()" :icon="Icon::Reaction">
        @include('characters.sezioni.partials.capacita', [
            'gruppo' => $capacita->get(ActionCost::Reaction->value),
            'introduzione' => null,
        ])
    </x-panel>
@endif


@if ($capacita->has(ActionCost::Passive->value))
    <x-panel title="Abilità passive" :icon="Icon::Passive">
        @include('characters.sezioni.partials.capacita', [
            'gruppo' => $capacita->get(ActionCost::Passive->value),
            'introduzione' => null,
        ])
    </x-panel>
@endif

{{-- I talenti sono inserimenti liberi e non hanno metadati strutturati su costo o utilizzi. --}}
@if ($character->feats->isNotEmpty())
    <x-panel title="Talenti" :icon="Icon::Talents">
        <ul class="space-y-3">
            @foreach ($character->feats as $talento)
                <li>
                    <p class="flex flex-wrap items-baseline gap-x-2">
                        <span class="font-medium text-fg">{{ $talento->name }}</span>
                        @if ($talento->level)
                            <span class="text-xs text-muted">{{ $talento->level }}º livello</span>
                        @endif
                    </p>
                    @if ($talento->description)
                        <p class="text-sm text-muted">{{ $talento->description }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-panel>
@endif

<details class="group mb-4 overflow-hidden rounded-card border border-line bg-surface">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3
                    text-sm font-semibold text-fg transition hover:bg-page [&::-webkit-details-marker]:hidden">
        <span class="flex items-center gap-2">
            <x-icona :is="Icon::General" class="h-4 w-4 shrink-0 text-muted" />
            Generali: le azioni base di chiunque
        </span>
        <x-icona :is="Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition group-open:rotate-180" />
    </summary>

    <ul class="space-y-3 border-t border-line p-4">
        @foreach (collect(config('dnd.actions')) as $azione)
            <li>
                <p class="flex flex-wrap items-baseline gap-x-2">
                    <span class="font-medium text-fg">{{ $azione['nome'] }}</span>
                    @if ($azione['costo'] === ActionCost::Reaction->value)
                        <span class="text-xs text-muted">· reazione</span>
                    @endif
                </p>
                <p class="text-sm text-muted">{{ $azione['testo'] }}</p>
            </li>
        @endforeach
    </ul>
</details>

@if ($senzaPrivilegi !== [])
    <x-note>
        Di {{ implode(' e ', $senzaPrivilegi) }} non abbiamo ancora scritto i privilegi:
        per adesso restano sul manuale.
        @if (filled($character->subclass_features))
            Quello che hai scritto tu si legge in «Storia».
        @endif
    </x-note>
@endif
