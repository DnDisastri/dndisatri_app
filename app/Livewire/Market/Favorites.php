<?php

namespace App\Livewire\Market;

use App\Models\Character;
use App\Models\MarketItem;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * I preferiti dell'emporio, sulla scheda del personaggio.
 *
 * Non è un secondo emporio: è l'elenco della roba che questo personaggio
 * ricompra sempre, con il prezzo e la strada per andarla a prendere. Comprare
 * si fa all'emporio, e il collegamento ci arriva **con il riquadro già aperto**
 * (`?oggetto=`), che è la differenza fra una scorciatoia e un promemoria.
 *
 * Da qui si può solo togliere la stella. Metterla vuol dire aver visto
 * l'articolo, e per vederlo si passa dall'emporio.
 *
 * L'id sta in `#[Locked]` e il personaggio si cerca comunque **fra i propri**:
 * la scheda la guardano anche gli altri, e la lista è privata come lo zaino.
 */
class Favorites extends Component
{
    #[Locked]
    public int $characterId;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    public function togli(int $itemId): void
    {
        if ($character = $this->character()) {
            $character->favoriteItems()->detach($itemId);
        }
    }

    /** Il personaggio, se è di chi sta guardando. Altrimenti niente. */
    private function character(): ?Character
    {
        return auth()->user()?->characters()->whereKey($this->characterId)->first();
    }

    public function render()
    {
        $character = $this->character();

        return view('livewire.market.favorites', [
            'mio' => $character !== null,
            'items' => $character?->favoriteItems ?? collect(),
        ]);
    }
}
