{{--
    Una schedina di incantesimo: la spunta a sinistra sceglie, il resto della
    riga apre la descrizione. Sono due comandi distinti di proposito — si può
    leggere senza scegliere, e scegliere senza aprire.

    Attende `$spell` e `$bloccato` (limite raggiunto e non ancora scelto);
    `$spells` e `$openSpells` arrivano dal componente.
--}}
@php
    $scelto = in_array($spell, $spells, true);
    $aperto = in_array($spell, $openSpells, true);
@endphp

<div @class([
        'overflow-hidden rounded-md border transition',
        'border-active bg-page' => $scelto,
        'border-line' => ! $scelto,
    ])>
    <div class="flex items-center gap-2 px-3 py-2 text-sm">
        <input type="checkbox" wire:model.live="spells" value="{{ $spell }}" class="accent-active"
               @disabled($bloccato)>

        <button type="button" wire:click="toggleSpell('{{ $spell }}')"
                class="flex flex-1 items-center gap-2 text-left">
            <span class="text-fg">{{ $spell }}</span>
            <x-icona :is="\App\Enums\Icon::Expand"
                     class="ml-auto h-4 w-4 shrink-0 text-muted transition {{ $aperto ? 'rotate-180' : '' }}" />
        </button>
    </div>

    @if ($aperto)
        <p class="px-3 pb-2.5 text-xs leading-relaxed text-muted">
            {{ \App\Domain\Dnd\SpellName::description($spell) }}
        </p>
    @endif
</div>
