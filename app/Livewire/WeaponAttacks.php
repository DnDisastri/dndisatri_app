<?php

namespace App\Livewire;

use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Gli attacchi del Turno: solo con le armi in mano, che si cambiano dall'inventario. */
class WeaponAttacks extends Component
{
    #[Locked]
    public int $characterId;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    #[On(InventoryManager::EQUIPAGGIAMENTO_CAMBIATO)]
    public function rinfresca(): void
    {
        //
    }

    public function render()
    {
        $character = Character::with(['items', 'weapons', 'itemEffects'])->findOrFail($this->characterId);

        return view('livewire.weapon-attacks', [
            'inMano' => $character->attacks()->where('equipped', true)->values(),
            'senzArmi' => $character->unarmedStrike(),
        ]);
    }
}
