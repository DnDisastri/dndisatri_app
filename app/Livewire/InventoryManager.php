<?php

namespace App\Livewire;

use App\Actions\Characters\AttuneItem;
use App\Actions\Characters\EquipItem;
use App\Models\Character;
use App\Models\CharacterItem;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * L'inventario con i comandi: indossare, riporre, andare in sintonia.
 *
 * Fino a qui l'inventario era un elenco da guardare. L'equipaggiamento veniva
 * assegnato una volta sola alla creazione, quindi comprare un'armatura migliore
 * non serviva a niente.
 */
class InventoryManager extends Component
{
    /** L'id non deve essere manomettibile dal browser: qui vive il permesso. */
    #[Locked]
    public int $characterId;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    /**
     * L'oro in cima si aggiorna quando un DM lo assegna dagli strumenti in
     * intestazione (M18): basta ridisegnarsi, il valore lo rilegge dal modello.
     */
    #[On('oro-cambiato')]
    public function rinfresca(): void
    {
        //
    }

    public function equip(int $itemId): void
    {
        $this->run($itemId, fn (CharacterItem $item) => app(EquipItem::class)->equip($item));
    }

    public function unequip(int $itemId): void
    {
        $this->run($itemId, fn (CharacterItem $item) => app(EquipItem::class)->unequip($item));
    }

    public function attune(int $itemId): void
    {
        $this->run(
            $itemId,
            fn (CharacterItem $item) => app(AttuneItem::class)->attune($item),
            ability: 'manageAttunement',
        );
    }

    public function release(int $itemId): void
    {
        $this->run(
            $itemId,
            fn (CharacterItem $item) => app(AttuneItem::class)->release($item),
            ability: 'manageAttunement',
        );
    }

    /**
     * La vetrina: «questo lo scambierei».
     *
     * È l'unica cosa che di questo zaino vedono gli altri, e per questo il
     * permesso è più stretto degli altri comandi — un DM non la tocca. Non è
     * una mossa di gioco: è una volontà del giocatore.
     */
    public function toggleTradeable(int $itemId): void
    {
        $this->run(
            $itemId,
            fn (CharacterItem $item) => $item->forceFill(['tradeable' => ! $item->tradeable])->save(),
            ability: 'manageTradeable',
        );
    }

    /**
     * Il permesso si chiede sul personaggio, e l'oggetto si cerca **fra i
     * suoi**: senza quel vincolo un id qualsiasi arrivato dal browser
     * lascerebbe spostare la roba di un altro.
     */
    private function run(int $itemId, callable $do, string $ability = 'manageEquipment'): void
    {
        $character = Character::findOrFail($this->characterId);
        $this->authorize($ability, $character);

        $item = $character->items()->whereKey($itemId)->firstOrFail();

        try {
            $do($item);
        } catch (\RuntimeException $e) {
            $this->addError('inventario', $e->getMessage());
        }
    }

    public function render()
    {
        $character = Character::with(['items', 'itemEffects'])->findOrFail($this->characterId);

        return view('livewire.inventory-manager', [
            'character' => $character,
            'items' => $character->items->sortBy('name'),
            // Quali oggetti portano un effetto: sono quelli per cui la sintonia
            // cambia qualcosa, e vanno distinti dal resto dello zaino.
            'magicItemIds' => $character->itemEffects->pluck('character_item_id')->filter()->unique(),
            'canManage' => auth()->user()?->can('manageEquipment', $character) ?? false,
            // Due permessi e non uno: la vetrina la decide solo il proprietario.
            'canShowcase' => auth()->user()?->can('manageTradeable', $character) ?? false,
        ]);
    }
}
