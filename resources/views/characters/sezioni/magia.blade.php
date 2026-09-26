<x-panel title="Incantesimi">
    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <x-stat label="Caratteristica" :value="$character->spellcastingAbility()?->label() ?? 'Vuoto'" />
        <x-stat label="CD" :value="$character->spellSaveDc() ?? 'Vuoto'" />
        <x-stat label="Attacco" :value="App\Domain\Dnd\Ability::format($character->spellAttackBonus() ?? 0)" />
        <x-stat label="Livello max" :value="$slots->maxSpellLevel() ?: 'Vuoto'" />
    </div>

    <div class="mb-4">
        <livewire:spell-slot-tracker :character="$character" :key="'slots-'.$character->id" />
    </div>

    <livewire:spell-book :character="$character" :key="'libro-'.$character->id" />
</x-panel>
