<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Enums\TradeStatus;
use App\Exceptions\ReversalException;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\MarketItem;
use App\Models\MarketListing;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Notifications\TransactionReversed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * L'annullamento di una transazione già conclusa (decisione D12).
 *
 * Non è uno strumento di uso corrente: serve quando due giocatori costruiscono
 * uno scambio in malafede e bisogna rendere il maltolto.
 *
 * **Se la roba non c'è più, l'annullamento si rifiuta e dice cosa manca.**
 * Quando l'oggetto è stato rivenduto o l'oro speso, il sistema non inventa:
 * spiega cosa lo blocca, e l'admin rimedia a mano con l'oro e il bottino.
 * L'alternativa — forzare mandando un saldo sotto zero — romperebbe la regola
 * per cui l'oro non scende mai sotto zero e lascerebbe uno stato che nessuna
 * schermata sa raccontare.
 *
 * Per la stessa ragione **si verifica tutto prima di muovere qualsiasi cosa**:
 * un annullamento fatto a metà sarebbe peggio del torto che voleva riparare.
 *
 * Il Registro non viene riscritto. L'annullamento **aggiunge** i suoi movimenti
 * in fondo, e segna sulla transazione che c'è stato un seguito.
 */
final class ReverseTransaction
{
    /** Uno scambio già accettato: tutto torna da dove era partito. */
    public function trade(Trade $trade, User $admin, string $reason): Trade
    {
        return DB::transaction(function () use ($trade, $admin, $reason) {
            $locked = Trade::whereKey($trade->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== TradeStatus::Accepted) {
                throw ReversalException::notReversible(
                    'Si annullano solo gli scambi andati a buon fine.'
                );
            }

            if ($locked->reversed_at !== null) {
                throw ReversalException::alreadyReversed();
            }

            $from = Character::whereKey($locked->from_character_id)->lockForUpdate()->firstOrFail();
            $to = Character::whereKey($locked->to_character_id)->lockForUpdate()->firstOrFail();

            $given = $locked->givenItems();
            $wanted = $locked->wantedItems();

            // Prima si verifica tutto, in entrambe le direzioni: un
            // annullamento fatto a metà sarebbe peggio del torto da riparare.
            $this->assertCanReturn($to, $given);
            $this->assertCanReturn($from, $wanted);
            $this->assertHasGold($to, $locked->give_gp);
            $this->assertHasGold($from, $locked->want_gp);

            // Poi si muove, all'indietro rispetto a com'era andata.
            $this->moveItems($given, from: $to, to: $from);
            $this->moveItems($wanted, from: $from, to: $to);

            $this->moveGold($to, $from, $locked->give_gp);
            $this->moveGold($from, $to, $locked->want_gp);

            $locked->forceFill([
                'reversed_at' => now(),
                'reversed_by' => $admin->getKey(),
            ])->save();

            // All'accettazione il proponente aveva guadagnato (want − give):
            // annullare significa restituirgli l'opposto.
            $undoForProposer = $locked->give_gp - $locked->want_gp;

            $this->record($from->refresh(), "Scambio con {$to->name} annullato", $undoForProposer, $admin, $reason);
            $this->record($to->refresh(), "Scambio con {$from->name} annullato", -$undoForProposer, $admin, $reason);

            return $locked;
        });
    }

    /** Una vendita fra giocatori: l'oggetto al venditore, l'oro al compratore. */
    public function listingSale(MarketListing $listing, User $admin, string $reason): MarketListing
    {
        return DB::transaction(function () use ($listing, $admin, $reason) {
            $locked = MarketListing::whereKey($listing->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->buyer_character_id === null) {
                throw ReversalException::notReversible('Questo annuncio non è stato venduto.');
            }

            if ($locked->reversed_at !== null) {
                throw ReversalException::alreadyReversed();
            }

            $seller = Character::whereKey($locked->seller_character_id)->lockForUpdate()->firstOrFail();
            $buyer = Character::whereKey($locked->buyer_character_id)->lockForUpdate()->firstOrFail();

            if (! $buyer->ownsItem($locked->name, $locked->qty)) {
                throw ReversalException::itemGone($buyer->name, $locked->name);
            }

            $this->assertHasGold($seller, $locked->price);

            $buyer->removeFromInventory($locked->name, $locked->qty);
            $seller->addToInventory(
                name: $locked->name,
                qty: $locked->qty,
                category: $locked->category,
                value: $locked->unit_value,
                details: $locked->details,
            );

            $this->moveGold($seller, $buyer, $locked->price);

            $locked->forceFill([
                'reversed_at' => now(),
                'reversed_by' => $admin->getKey(),
            ])->save();

            $this->record($seller->refresh(), "Vendita di {$locked->name} annullata", -$locked->price, $admin, $reason);
            $this->record($buyer->refresh(), "Acquisto di {$locked->name} annullato", $locked->price, $admin, $reason);

            return $locked;
        });
    }

    /** Un acquisto dal negozio: oro indietro, oggetto via, scorte ripristinate. */
    public function shopPurchase(LedgerEntry $entry, User $admin, string $reason): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $admin, $reason) {
            $locked = LedgerEntry::whereKey($entry->getKey())->lockForUpdate()->firstOrFail();

            $this->assertReversableEntry($locked, LedgerAction::Buy);

            $details = $locked->details ?? [];
            $name = $details['name'] ?? null;
            $qty = (int) ($details['qty'] ?? 0);

            if ($name === null || $qty < 1) {
                throw ReversalException::notReversible(
                    'Di questo acquisto non è stato registrato cosa è stato comprato: '
                    .'è precedente all\'introduzione degli annullamenti.'
                );
            }

            $character = Character::whereKey($locked->character_id)->lockForUpdate()->firstOrFail();

            if (! $character->ownsItem($name, $qty)) {
                throw ReversalException::itemGone($character->name, $name);
            }

            $character->removeFromInventory($name, $qty);
            // `gp_delta` era negativo: rimetterlo indietro è sottrarlo.
            $character->increment('gp', -$locked->gp_delta);

            if ($item = MarketItem::find($details['market_item_id'] ?? null)) {
                if (! $item->is_unlimited) {
                    $item->increment('stock', $qty);
                }
            }

            $this->close($locked, $admin);
            $this->record($character->refresh(), "Acquisto di {$qty}× {$name} annullato", -$locked->gp_delta, $admin, $reason);

            return $locked;
        });
    }

    /** L'oro assegnato o tolto da un DM, rimesso com'era. */
    public function goldGrant(LedgerEntry $entry, User $admin, string $reason): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $admin, $reason) {
            $locked = LedgerEntry::whereKey($entry->getKey())->lockForUpdate()->firstOrFail();

            $this->assertReversableEntry($locked, LedgerAction::DmGold);

            $character = Character::whereKey($locked->character_id)->lockForUpdate()->firstOrFail();
            $undo = -$locked->gp_delta;

            if ($undo < 0 && $character->gp < abs($undo)) {
                throw ReversalException::goldGone($character->name, abs($undo), $character->gp);
            }

            $character->increment('gp', $undo);

            $this->close($locked, $admin);
            $this->record($character->refresh(), 'Assegnazione di oro annullata', $undo, $admin, $reason);

            return $locked;
        });
    }

    // === Attrezzi comuni ===

    private function assertReversableEntry(LedgerEntry $entry, LedgerAction $expected): void
    {
        if ($entry->action !== $expected) {
            throw ReversalException::notReversible(
                'Questa riga del Registro non è di quel tipo: '.$entry->action->label().'.'
            );
        }

        if ($entry->reversed_at !== null) {
            throw ReversalException::alreadyReversed();
        }
    }

    /** @param Collection<int,TradeItem> $items */
    private function assertCanReturn(Character $holder, Collection $items): void
    {
        foreach ($items as $item) {
            if (! $holder->ownsItem($item->name, $item->qty)) {
                throw ReversalException::itemGone($holder->name, $item->name);
            }
        }
    }

    private function assertHasGold(Character $character, int $amount): void
    {
        if ($amount > 0 && $character->gp < $amount) {
            throw ReversalException::goldGone($character->name, $amount, $character->gp);
        }
    }

    /** @param Collection<int,TradeItem> $items */
    private function moveItems(Collection $items, Character $from, Character $to): void
    {
        foreach ($items as $item) {
            $from->removeFromInventory($item->name, $item->qty);
            $to->addToInventory(
                name: $item->name,
                qty: $item->qty,
                category: $item->category,
                value: $item->value,
                details: $item->details,
            );
        }
    }

    private function moveGold(Character $from, Character $to, int $amount): void
    {
        if ($amount > 0) {
            $from->decrement('gp', $amount);
            $to->increment('gp', $amount);
        }
    }

    private function close(LedgerEntry $entry, User $admin): void
    {
        $entry->forceFill([
            'reversed_at' => now(),
            'reversed_by' => $admin->getKey(),
        ])->save();
    }

    private function record(Character $character, string $what, int $gpDelta, User $admin, string $reason): void
    {
        $character->recordInLedger(
            LedgerAction::Reversal,
            "{$what} — {$reason}",
            $gpDelta,
            $admin,
        );

        $character->user()->first()?->notify(new TransactionReversed($what, $reason));
    }
}
