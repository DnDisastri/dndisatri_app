<?php

namespace App\Livewire;

use App\Actions\Characters\SpendSpellSlot;
use App\Domain\Dnd\SpellSlotSet;
use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Gli slot incantesimo sulla scheda: si consumano, si recuperano, e i riposi
 * li rimettono a posto.
 *
 * È l'unica parte della scheda che il giocatore modifica direttamente, perché
 * è lo stato di una serata e non una modifica al personaggio.
 */
class SpellSlotTracker extends Component
{
    /** L'id non deve essere manomettibile dal browser: qui vive il permesso. */
    #[Locked]
    public int $characterId;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    public function spend(int|string $slot): void
    {
        $character = $this->character();
        $this->authorize('manageSlots', $character);

        try {
            app(SpendSpellSlot::class)->spend($character, $slot);
        } catch (\RuntimeException $e) {
            $this->addError('slot', $e->getMessage());
        }
    }

    public function recover(int|string $slot): void
    {
        $character = $this->character();
        $this->authorize('manageSlots', $character);

        app(SpendSpellSlot::class)->recover($character, $slot);
    }

    /**
     * Un riposo lo prende l'intestazione, che sta su tutte le sezioni: qui
     * serve solo ridisegnarsi, perché gli slot li ha appena cambiati un altro
     * componente e questo non se ne accorgerebbe.
     */
    #[On('riposo-preso')]
    public function rinfresca(): void
    {
        //
    }

    private function character(): Character
    {
        return Character::findOrFail($this->characterId);
    }

    public function render()
    {
        $character = $this->character();

        /*
         * Due riserve, non una. Un Warlock 3 / Stregone 2 ha gli slot normali
         * (dallo Stregone) **e** quelli da patto (dal Warlock), che si
         * recuperano diversamente: il patto torna anche col riposo breve. Prima
         * la scheda mostrava solo i normali, e la riserva da patto spariva.
         *
         * Un Warlock **puro** è il caso da non sbagliare: `spellSlots()` per lui
         * restituisce già la riserva da patto (`isPact`). Disegnarla anche come
         * «normale» la mostrerebbe due volte — quindi lì i normali non ci sono,
         * e a mostrare il patto ci pensa `$pact`.
         */
        $standard = $character->spellSlots();
        $pact = $character->pactSlots();

        if ($standard->isPact) {
            $standard = SpellSlotSet::none();
        }

        return view('livewire.spell-slot-tracker', [
            'character' => $character,
            // Non si possono chiamare `slots`: in Livewire 4 quel nome è
            // riservato agli slot dei componenti, e la vista riceverebbe un
            // SlotProxy.
            'standard' => $standard,
            'pact' => $pact,
            'used' => $character->spell_slots_used ?? [],
            'canManage' => auth()->user()?->can('manageSlots', $character) ?? false,
        ]);
    }

    /** La chiave con cui uno slot è segnato: il livello, o `pact`. */
    public static function keyFor(SpellSlotSet $slots, int $level): int|string
    {
        return $slots->isPact ? 'pact' : $level;
    }
}
