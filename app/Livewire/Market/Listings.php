<?php

namespace App\Livewire\Market;

use App\Actions\Market\CancelListing;
use App\Actions\Supervision\Supervisor;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use Livewire\Component;

/**
 * Gli annunci fra giocatori.
 *
 * Mettere in vendita e comprare passano dal **Supervisor**, non dalle azioni
 * dirette: è lui a decidere se eseguire subito o trattenere in attesa di un via
 * libera, per chi è sotto richiamo (D13).
 *
 * Chiamare qui `CreateListing` o `BuyListing` funzionerebbe benissimo e farebbe
 * sparire il controllo **senza che nessun test se ne accorga**, perché i test
 * della vigilanza chiamano il Supervisor e non questa pagina.
 *
 * Ritirare un proprio annuncio invece è diretto: non c'è niente da vigilare in
 * chi si riprende la propria roba.
 */
class Listings extends Component
{
    use ActsAsCharacter;

    public string $itemName = '';

    public int $sellQty = 1;

    public int $price = 0;

    /** L'annuncio aperto nel riquadro di dettaglio. */
    public ?int $aperto = null;

    /** Quello che si sta cercando fra gli annunci. */
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

        try {
            $result = app(Supervisor::class)->createListing(
                auth()->user(), $character, $this->itemName, $this->sellQty, $this->price,
            );

            $this->reset('itemName', 'sellQty', 'price');
            session()->flash('mercato', $this->outcome($result, 'Annuncio pubblicato.'));
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
            session()->flash('mercato', $this->outcome($result, "Comprato: {$listing->name}."));
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
            session()->flash('mercato', 'Annuncio ritirato.');
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

        /*
         * I propri stanno da una parte e quelli degli altri dall'altra: sono due
         * cose che si fanno in due momenti diversi — ritirare la propria roba, o
         * comprare quella di qualcun altro — e mescolate costringevano a leggere
         * ogni card per capire quale delle due si stava guardando.
         */
        [$miei, $altrui] = $listings->partition(
            fn (MarketListing $listing) => $character && $listing->seller_character_id === $character->getKey(),
        );

        return view('livewire.market.listings', [
            'character' => $character,
            'miei' => $miei,
            'listings' => $altrui,
            'mine' => $character?->items->sortBy('name') ?? collect(),
            // L'annuncio aperto si cerca a parte: fra l'apertura del riquadro e
            // il momento in cui si preme può essere stato comprato da un altro,
            // e allora sparisce anche di qui.
            'annuncio' => $this->aperto
                ? MarketListing::where('status', 'active')->with('seller')->find($this->aperto)
                : null,
        ])->title('Annunci');
    }
}
