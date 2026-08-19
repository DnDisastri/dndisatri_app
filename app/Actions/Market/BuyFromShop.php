<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Acquisto dal negozio della gilda.
 *
 * Tutto dentro una transazione, con **entrambe** le righe bloccate: quella
 * dell'articolo e quella del personaggio. Servono tutte e due —
 *
 * - senza il blocco sull'articolo, due giocatori che comprano nello stesso
 *   istante l'ultimo pezzo passerebbero entrambi il controllo sulle scorte e
 *   lo stock finirebbe sotto zero;
 * - senza il blocco sul personaggio, due acquisti simultanei dello stesso
 *   giocatore (due schede aperte) potrebbero scalare l'oro da un saldo letto
 *   prima dell'altro acquisto, e farlo spendere più di quanto ha.
 *
 * Nella vecchia applicazione l'oro e le scorte li scriveva il client, e le
 * regole Firestore lasciavano modificare `gp` e `items` di chiunque (§8.2).
 */
final class BuyFromShop
{
    public function handle(Character $character, MarketItem $item, int $qty = 1, ?User $actor = null): Character
    {
        if ($qty < 1) {
            throw MarketException::invalidQuantity();
        }

        return DB::transaction(function () use ($character, $item, $qty, $actor) {
            $item = MarketItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $buyer = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();

            if (! $item->isAvailable($qty)) {
                throw MarketException::outOfStock($item->name);
            }

            $cost = $item->totalPrice($qty);

            if ($buyer->gp < $cost) {
                throw MarketException::notEnoughGold($cost, $buyer->gp);
            }

            $buyer->decrement('gp', $cost);

            if (! $item->is_unlimited) {
                $item->decrement('stock', $qty);
            }

            $buyer->refresh()->addToInventory(
                name: $item->name,
                qty: $qty,
                category: $item->category,
                value: $item->price,
                details: $item->details,
            );

            $buyer->recordInLedger(
                LedgerAction::Buy,
                $qty > 1
                    ? "Acquisto di {$qty}× {$item->name} dal negozio della gilda"
                    : "Acquisto di {$item->name} dal negozio della gilda",
                -$cost,
                $actor,
                // Da una frase in italiano non si torna indietro: qui gli
                // stessi dati in forma utilizzabile, per l'annullamento.
                [
                    'market_item_id' => $item->getKey(),
                    'name' => $item->name,
                    'qty' => $qty,
                ],
            );

            return $buyer;
        });
    }
}
