<?php

namespace App\Livewire\Market;

use App\Actions\Market\BuyFromShop;
use App\Exceptions\MarketException;
use App\Livewire\Concerns\ActsAsCharacter;
use App\Models\MarketItem;
use Livewire\Component;

/**
 * Il negozio della gilda.
 *
 * **Non passa dal Supervisor**, ed è l'unica azione di mercato che non lo fa: i
 * prezzi li fissano gli admin e le scorte sono comuni, quindi non c'è nessuno
 * da truffare e un richiamo non lo tocca (D13).
 *
 * La griglia mostra il minimo — nome, categoria, prezzo — e tutto il resto,
 * compreso il comprare, sta nel riquadro di dettaglio: in due colonne su un
 * telefono non ci stanno una casella per la quantità e un pulsante accanto al
 * prezzo senza che si accavallino.
 *
 * I preferiti stanno in cima e sono del personaggio, non del giocatore: vedi la
 * migrazione di `character_market_item`.
 */
class Shop extends Component
{
    use ActsAsCharacter;

    /** L'articolo aperto nel riquadro di dettaglio. */
    public ?int $aperto = null;

    /** Quanti pezzi comprare: uno alla volta, del solo articolo aperto. */
    public int $quanti = 1;

    /** Quello che si sta cercando: nome, categoria o descrizione. */
    public string $cerca = '';

    /**
     * Si può arrivare qui con un articolo già in mente — dalla scheda del
     * personaggio, dove i preferiti sono scorciatoie. L'id passa dall'indirizzo
     * e quindi non ci si fida: si apre solo se esiste davvero.
     */
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

        // Un errore di un acquisto precedente non appartiene a questo riquadro.
        $this->resetErrorBag('mercato');
    }

    public function chiudi(): void
    {
        $this->aperto = null;
    }

    /**
     * La stella: mette o toglie il preferito.
     *
     * Il personaggio arriva da `requireCharacter()`, che lo cerca **fra i
     * propri**: un id altrui arrivato dal browser non viene trovato, ed è lì che
     * sta la sicurezza — non nel fatto che il campo sia nascosto.
     */
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

            // Comprato: il riquadro ha finito il suo lavoro e si toglie di
            // mezzo, così il messaggio in cima si legge.
            $this->chiudi();
            session()->flash('mercato', "Comprato: {$item->name}.");
        } catch (MarketException $e) {
            $this->addError('mercato', $e->getMessage());
        }
    }

    public function render()
    {
        $character = $this->character()?->loadMissing('favoriteItems');

        $items = MarketItem::available()
            ->when($this->cerca !== '', function ($query) {
                // Nome, categoria e descrizione insieme: chi cerca «cura» non
                // sa se sta cercando un nome o una famiglia di oggetti, e
                // «recupera punti ferita» sta scritto solo nel dettaglio.
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
            // L'articolo aperto si cerca a parte e non dentro la griglia:
            // arrivando da un preferito potrebbe essere esaurito, e allora
            // nella griglia non c'è ma il riquadro deve dirlo lo stesso.
            'oggetto' => $this->aperto ? MarketItem::find($this->aperto) : null,
        ])->title('Negozio della gilda');
    }
}
