<?php

namespace App\Livewire;

use App\Actions\Characters\AttuneItem;
use App\Actions\Characters\EquipItem;
use App\Actions\Market\Purse;
use App\Domain\Dnd\Coin;
use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\CharacterItem;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Borsa e inventario con i comandi: cambiare monete, indossare, riporre, andare in sintonia. */
class InventoryManager extends Component
{
    /** L'id non deve essere manomettibile dal browser: qui vive il permesso. */
    #[Locked]
    public int $characterId;

    public bool $modaleCambio = false;

    public string $cambioDa = 'gp';

    public string $cambioA = 'pp';

    public ?int $cambioQuante = null;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    /** La borsa si aggiorna quando un DM la tocca dagli strumenti in intestazione. */
    #[On('oro-cambiato')]
    public function rinfresca(): void
    {
        //
    }

    public function apriCambio(): void
    {
        $this->authorize('manageEquipment', Character::findOrFail($this->characterId));
        $this->reset('cambioDa', 'cambioA', 'cambioQuante');
        $this->resetErrorBag();
        $this->modaleCambio = true;
    }

    public function chiudiCambio(): void
    {
        $this->modaleCambio = false;
    }

    public function cambia(): void
    {
        $character = Character::findOrFail($this->characterId);
        $this->authorize('manageEquipment', $character);

        $this->validate([
            'cambioDa' => ['required', Rule::enum(Coin::class)],
            'cambioA' => ['required', Rule::enum(Coin::class), 'different:cambioDa'],
            'cambioQuante' => ['required', 'integer', 'min:1'],
        ], [
            'cambioA.different' => 'Scegli due monete diverse.',
            'cambioQuante.required' => 'Quante monete vuoi cambiare?',
        ]);

        try {
            app(Purse::class)->convert(
                $character, Coin::from($this->cambioDa), Coin::from($this->cambioA), $this->cambioQuante, auth()->user(),
            );
        } catch (MarketException $e) {
            $this->addError('cambioQuante', $e->getMessage());

            return;
        }

        $this->modaleCambio = false;
    }

    /** L'anteprima del cambio, o null finché non è un cambio valido. */
    private function anteprimaCambio(Character $character): ?Coins
    {
        $da = Coin::tryFrom($this->cambioDa);
        $a = Coin::tryFrom($this->cambioA);

        if ($da === null || $a === null || ! $this->cambioQuante) {
            return null;
        }

        try {
            return $character->coins()->plus($character->coins()->conversion($da, $a, $this->cambioQuante));
        } catch (InvalidArgumentException) {
            return null;
        }
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
            'anteprima' => $this->modaleCambio ? $this->anteprimaCambio($character) : null,
        ]);
    }
}
