<?php

namespace App\Livewire;

use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Cosa ha addosso e quali oggetti magici: si cambia dall'inventario, qui si ridisegna. */
class EquipmentSummary extends Component
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
        return view('livewire.equipment-summary', [
            'character' => Character::with(['items', 'itemEffects'])->findOrFail($this->characterId),
        ]);
    }
}
