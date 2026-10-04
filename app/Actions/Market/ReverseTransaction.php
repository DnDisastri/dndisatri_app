<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
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
 * L'annullamento di una transazione conclusa, per gli scambi in malafede.
 *
 * Se la roba non c'è più si rifiuta e dice cosa manca: niente borse sotto zero.
 * Si verifica tutto prima di muovere qualsiasi cosa, e il Registro non si
 * riscrive: l'annullamento aggiunge le sue righe in fondo.
 */
final class ReverseTransaction
{
    public function __construct(private readonly Purse $purse) {}

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

            $this->assertCanReturn($to, $given);
            $this->assertCanReturn($from, $wanted);
            $this->assertHasCoins($to, $locked->give_cp);
            $this->assertHasCoins($from, $locked->want_cp);

            $this->moveItems($given, from: $to, to: $from);
            $this->moveItems($wanted, from: $from, to: $to);

            [$toDelta, $fromDelta] = $this->moveCoins($to, $from, $locked->give_cp);
            [$fromBack, $toBack] = $this->moveCoins($from, $to, $locked->want_cp);

            $locked->forceFill([
                'reversed_at' => now(),
                'reversed_by' => $admin->getKey(),
            ])->save();

            $this->record($from, "Scambio con {$to->name} annullato", $fromDelta->plus($fromBack), $admin, $reason);
            $this->record($to, "Scambio con {$from->name} annullato", $toDelta->plus($toBack), $admin, $reason);

            return $locked;
        });
    }

    /** Una vendita fra giocatori: l'oggetto al venditore, le monete al compratore. */
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

            $this->assertHasCoins($seller, $locked->price_cp);

            $buyer->removeFromInventory($locked->name, $locked->qty);
            $seller->addToInventory(...Character::itemCopy($locked), qty: $locked->qty);

            [$sellerDelta, $buyerDelta] = $this->moveCoins($seller, $buyer, $locked->price_cp);

            $locked->forceFill([
                'reversed_at' => now(),
                'reversed_by' => $admin->getKey(),
            ])->save();

            $this->record($seller, "Vendita di {$locked->name} annullata", $sellerDelta, $admin, $reason);
            $this->record($buyer, "Acquisto di {$locked->name} annullato", $buyerDelta, $admin, $reason);

            return $locked;
        });
    }

    /** Un acquisto dal negozio: monete indietro, oggetto via, scorte ripristinate. */
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
            $delta = $this->purse->revert($character, $locked->coinsDelta());

            if ($item = MarketItem::find($details['market_item_id'] ?? null)) {
                if (! $item->is_unlimited) {
                    $item->increment('stock', $qty);
                }
            }

            $this->close($locked, $admin);
            $this->record($character, "Acquisto di {$qty}× {$name} annullato", $delta, $admin, $reason);

            return $locked;
        });
    }

    /** Le monete date o tolte da un DM, rimesse com'erano. */
    public function goldGrant(LedgerEntry $entry, User $admin, string $reason): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $admin, $reason) {
            $locked = LedgerEntry::whereKey($entry->getKey())->lockForUpdate()->firstOrFail();

            $this->assertReversableEntry($locked, LedgerAction::DmGold);

            $character = Character::whereKey($locked->character_id)->lockForUpdate()->firstOrFail();
            $this->assertHasCoins($character, $locked->cp_delta);

            $delta = $this->purse->revert($character, $locked->coinsDelta());

            $this->close($locked, $admin);
            $this->record($character, 'Assegnazione di monete annullata', $delta, $admin, $reason);

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

    private function assertHasCoins(Character $character, int $cp): void
    {
        if ($cp > 0 && $character->purseValue() < $cp) {
            throw ReversalException::coinsGone($character->name, $cp, $character->purseValue());
        }
    }

    /** @param Collection<int,TradeItem> $items */
    private function moveItems(Collection $items, Character $from, Character $to): void
    {
        foreach ($items as $item) {
            $from->removeFromInventory($item->name, $item->qty);
            $to->addToInventory(...Character::itemCopy($item), qty: $item->qty);
        }
    }

    /** @return array{0: Coins, 1: Coins} i movimenti di chi paga e di chi riceve */
    private function moveCoins(Character $from, Character $to, int $cp): array
    {
        if ($cp <= 0) {
            return [Coins::none(), Coins::none()];
        }

        return [$this->purse->pay($from, $cp), $this->purse->receiveValue($to, $cp)];
    }

    private function close(LedgerEntry $entry, User $admin): void
    {
        $entry->forceFill([
            'reversed_at' => now(),
            'reversed_by' => $admin->getKey(),
        ])->save();
    }

    private function record(Character $character, string $what, Coins $delta, User $admin, string $reason): void
    {
        $character->recordInLedger(
            LedgerAction::Reversal,
            "{$what} ({$reason})",
            $delta,
            $admin,
        );

        $character->user()->first()?->notify(new TransactionReversed($what, $reason));
    }
}
