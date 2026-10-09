<?php

namespace App\Livewire\Market;

use App\Actions\Characters\ProposeChange;
use App\Actions\Market\BuyFromShop;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\MarketItem;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Component;

/**
 * L'acquisto è l'unica azione di mercato che non passa dal Supervisor: prezzi e
 * scorte li decidono gli admin, non c'è nessuno da truffare. Il baratto invece
 * dà un valore a un oggetto del giocatore, e lo approva un DM.
 */
class Shop extends Component
{
    use ActsAsCharacter;

    public ?int $aperto = null;

    public int $quanti = 1;

    public string $cerca = '';

    /** Una delle chiavi di ORDINI; un valore manomesso ricade sulla categoria. */
    public string $ordine = 'categoria';

    public const ORDINI = [
        'categoria' => 'Categoria',
        'prezzo' => 'Prezzo ↑',
        '-prezzo' => 'Prezzo ↓',
        'nome' => 'Nome A-Z',
        '-nome' => 'Nome Z-A',
    ];

    public ?int $offerta = null;

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
        $this->aperto = MarketItem::onSale()->whereKey($itemId)->value('id');
        $this->quanti = 1;
        $this->offerta = null;

        $this->resetErrorBag(['mercato', 'baratto']);
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

    /** Non sposta niente: diventa una richiesta per DM e admin. */
    public function barter(int $itemId): void
    {
        $character = $this->requireCharacter();
        $wanted = MarketItem::onSale()->findOrFail($itemId);

        if ($this->offerta === null) {
            $this->addError('baratto', 'Scegli quale oggetto offri.');

            return;
        }

        // Fra i propri oggetti: un id arrivato dal browser non apre lo zaino di un altro.
        $given = $character->items()->whereKey($this->offerta)->first();

        if ($given === null) {
            $this->addError('baratto', 'Quell\'oggetto non è nel tuo zaino.');

            return;
        }

        try {
            app(ProposeChange::class)->barter($character, auth()->user(), $given, $wanted);
        } catch (InvalidArgumentException $e) {
            $this->addError('baratto', $e->getMessage());

            return;
        }

        $this->chiudi();
        $this->esito("Baratto proposto: {$given->name} per {$wanted->name}. Lo approva un dungeon master, lo trovi in «Le mie richieste».");
    }

    /**
     * Gli oggetti che bastano per l'articolo: valgono almeno il prezzo.
     *
     * @return Collection<int, CharacterItem>
     */
    private function offribili(?Character $character, ?MarketItem $oggetto): Collection
    {
        if ($character === null || $oggetto === null || ! $oggetto->isAvailable()) {
            return collect();
        }

        return $character->items
            ->filter(fn (CharacterItem $item) => $item->value_cp >= max(1, $oggetto->price_cp))
            ->unique('name')
            ->sortBy('name')
            ->values();
    }

    public function render()
    {
        $character = $this->character()?->loadMissing('favoriteItems');

        $oggetto = $this->aperto ? MarketItem::onSale()->find($this->aperto) : null;

        $items = MarketItem::available()
            ->when($this->cerca !== '', function ($query) {
                $parola = '%'.$this->cerca.'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $parola)
                    ->orWhere('category', 'like', $parola)
                    ->orWhere('details', 'like', $parola));
            })
            ->tap(fn ($query) => match ($this->ordine) {
                'prezzo' => $query->orderBy('price_cp')->orderBy('name'),
                '-prezzo' => $query->orderByDesc('price_cp')->orderBy('name'),
                'nome' => $query->orderBy('name'),
                '-nome' => $query->orderByDesc('name'),
                default => $query->orderBy('category')->orderBy('name'),
            })
            ->get();
        $preferiti = $character?->favoriteItems->pluck('id') ?? collect();

        [$stellati, $resto] = $items->partition(fn (MarketItem $item) => $preferiti->contains($item->getKey()));

        return view('livewire.market.shop', [
            'character' => $character,
            'preferiti' => $stellati,
            'items' => $resto,
            'stelle' => $preferiti,
            // Cercato a parte: da un preferito può arrivare un articolo esaurito, assente dalla griglia.
            'oggetto' => $oggetto,
            'offribili' => $this->offribili($character, $oggetto),
        ]);
    }
}
