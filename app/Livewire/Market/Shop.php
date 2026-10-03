<?php

namespace App\Livewire\Market;

use App\Actions\Market\BuyFromShop;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\MarketItem;
use Livewire\Component;

/**
 * Unica azione di mercato che non passa dal Supervisor: prezzi e scorte li
 * decidono gli admin, non c'è nessuno da truffare.
 */
class Shop extends Component
{
    use ActsAsCharacter;

    public ?int $aperto = null;

    public int $quanti = 1;

    public string $cerca = '';

    /** L'articolo da aprire arriva dall'indirizzo: si apre solo se esiste. */
    public function mount(): void
    {
        $this->resolveCharacter();

        if ($oggetto = (int) request()->query('oggetto')) {
            $this->apri($oggetto);
        }
    }

    public function apri(int $itemId): void
    {
        $this->aperto = MarketItem::whereKey($itemId)->value('id');
        $this->quanti = 1;

        $this->resetErrorBag('mercato');
    }

    public function chiudi(): void
    {
        $this->aperto = null;
    }

    /** La sicurezza sta in `requireCharacter()`, che cerca solo fra i propri personaggi. */
    public function preferisci(int $itemId): void
    {
        $this->requireCharacter()->toggleFavorite(MarketItem::findOrFail($itemId));
    }

    public function buy(int $itemId): void
    {
        $character = $this->requireCharacter();
        $item = MarketItem::findOrFail($itemId);

        try {
            app(BuyFromShop::class)->handle(
                $character,
                $item,
                max(1, $this->quanti),
                auth()->user(),
            );

            $this->chiudi();
            $this->esito("Comprato: {$item->name}.");
        } catch (MarketException $e) {
            $this->addError('mercato', $e->getMessage());
        }
    }

    public function render()
    {
        $character = $this->character()?->loadMissing('favoriteItems');

        $items = MarketItem::available()
            ->when($this->cerca !== '', function ($query) {
                $parola = '%'.$this->cerca.'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $parola)
                    ->orWhere('category', 'like', $parola)
                    ->orWhere('details', 'like', $parola));
            })
            ->orderBy('category')->orderBy('name')->get();
        $preferiti = $character?->favoriteItems->pluck('id') ?? collect();

        [$stellati, $resto] = $items->partition(fn (MarketItem $item) => $preferiti->contains($item->getKey()));

        return view('livewire.market.shop', [
            'character' => $character,
            'preferiti' => $stellati,
            'items' => $resto,
            'stelle' => $preferiti,
            // Cercato a parte: da un preferito può arrivare un articolo esaurito, assente dalla griglia.
            'oggetto' => $this->aperto ? MarketItem::find($this->aperto) : null,
        ]);
    }
}
