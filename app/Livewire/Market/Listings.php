<?php

namespace App\Livewire\Market;

use App\Actions\Market\CancelListing;
use App\Actions\Supervision\Supervisor;
use App\Domain\Dnd\Coins;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use Livewire\Component;

/**
 * Vendere e comprare passano dal Supervisor (vigilanza su chi è sotto richiamo).
 * Chiamare qui `CreateListing` o `BuyListing` salterebbe il controllo senza che
 * nessun test se ne accorga: i test della vigilanza chiamano il Supervisor.
 */
class Listings extends Component
{
    use ActsAsCharacter;

    public string $itemName = '';

    public int $sellQty = 1;

    /** @var array<string,int|string|null> il prezzo, pila per pila */
    public array $price = [];

    public ?int $aperto = null;

    public string $cerca = '';

    public function mount(): void
    {
        $this->resolveCharacter();
    }

    public function apri(int $listingId): void
    {
        $this->aperto = MarketListing::whereKey($listingId)->value('id');
        $this->resetErrorBag('mercato');
    }

    public function chiudi(): void
    {
        $this->aperto = null;
    }

    public function sell(): void
    {
        $character = $this->requireCharacter();

        $this->validate([
            'price.*' => ['nullable', 'integer', 'min:0', 'max:'.Coins::MAX],
        ], [
            'price.*.min' => 'Il prezzo non può essere negativo.',
            'price.*.max' => 'Prezzo troppo alto: tante monete non esistono.',
        ]);

        $prezzo = Coins::fromArray($this->price)->value();

        if ($prezzo > Coins::MAX) {
            $this->addError('price', 'Prezzo troppo alto: tante monete non esistono.');

            return;
        }

        try {
            $result = app(Supervisor::class)->createListing(
                auth()->user(), $character, $this->itemName, $this->sellQty, $prezzo,
            );

            $this->reset('itemName', 'sellQty', 'price');
            $this->esito($this->outcome($result, 'Annuncio pubblicato.'));
        } catch (MarketException $e) {
            $this->addError('mercato', $e->getMessage());
        }
    }

    public function buy(int $listingId): void
    {
        $character = $this->requireCharacter();
        $listing = MarketListing::findOrFail($listingId);

        try {
            $result = app(Supervisor::class)->buyListing(auth()->user(), $listing, $character);

            $this->chiudi();
            $this->esito($this->outcome($result, "Comprato: {$listing->name}."));
        } catch (MarketException $e) {
            $this->addError('mercato', $e->getMessage());
        }
    }

    public function withdraw(int $listingId): void
    {
        $listing = MarketListing::findOrFail($listingId);
        $this->authorize('cancel', $listing);

        try {
            app(CancelListing::class)->handle($listing, auth()->user());

            $this->chiudi();
            $this->esito('Annuncio ritirato.');
        } catch (MarketException $e) {
            $this->addError('mercato', $e->getMessage());
        }
    }

    /** Un'azione trattenuta non è un errore: è un'attesa, e va detto. */
    private function outcome(object $result, string $done): string
    {
        return $result instanceof SupervisedAction
            ? 'Sei sotto richiamo: la richiesta è in attesa che un DM la approvi.'
            : $done;
    }

    public function render()
    {
        $character = $this->character();

        $listings = MarketListing::where('status', 'active')
            ->when($this->cerca !== '', function ($query) {
                $parola = '%'.$this->cerca.'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $parola)
                    ->orWhere('category', 'like', $parola)
                    ->orWhere('details', 'like', $parola));
            })
            ->with('seller')
            ->latest()
            ->get();

        [$miei, $altrui] = $listings->partition(
            fn (MarketListing $listing) => $character && $listing->seller_character_id === $character->getKey(),
        );

        return view('livewire.market.listings', [
            'character' => $character,
            'miei' => $miei,
            'listings' => $altrui,
            'mine' => $character?->items->sortBy('name') ?? collect(),
            // Cercato a parte: nel frattempo può averlo comprato un altro.
            'annuncio' => $this->aperto
                ? MarketListing::where('status', 'active')->with('seller')->find($this->aperto)
                : null,
        ]);
    }
}
