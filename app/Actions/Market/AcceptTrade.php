<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Notifications\TradeResolved;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La disponibilità si verifica adesso, per entrambe le parti. Prima si verifica
 * tutto, poi si sposta tutto: a metà strada uno dei due resterebbe senza la sua parte.
 */
final class AcceptTrade
{
    public function handle(Trade $trade, ?User $actor = null): Trade
    {
        return DB::transaction(function () use ($trade, $actor) {
            $locked = Trade::whereKey($trade->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new MarketException('Questa proposta di scambio è già stata chiusa.');
            }

            // Sempre per chiave crescente: due scambi incrociati accettati insieme andrebbero in deadlock.
            [$from, $to] = $this->lockBothInStableOrder($locked);

            $given = $locked->givenItems();
            $wanted = $locked->wantedItems();

            $this->assertCanDeliver($from, $given, $locked->give_cp);
            $this->assertCanDeliver($to, $wanted, $locked->want_cp);

            $this->move($given, from: $from, to: $to);
            $this->move($wanted, from: $to, to: $from);

            $fromDelta = Coins::none();
            $toDelta = Coins::none();
            $purse = app(Purse::class);

            if ($locked->give_cp > 0) {
                $fromDelta = $fromDelta->plus($purse->pay($from, $locked->give_cp));
                $toDelta = $toDelta->plus($purse->receiveValue($to, $locked->give_cp));
            }

            if ($locked->want_cp > 0) {
                $toDelta = $toDelta->plus($purse->pay($to, $locked->want_cp));
                $fromDelta = $fromDelta->plus($purse->receiveValue($from, $locked->want_cp));
            }

            $locked->forceFill([
                'status' => TradeStatus::Accepted,
                'resolved_at' => now(),
            ])->save();

            $from->recordInLedger(LedgerAction::Trade, "Scambio con {$to->name}", $fromDelta, $actor);
            $to->recordInLedger(LedgerAction::Trade, "Scambio con {$from->name}", $toDelta, $actor);

            $from->user()->first()?->notify(new TradeResolved($locked, $to->name));

            return $locked;
        });
    }

    /** @return array{0: Character, 1: Character} */
    private function lockBothInStableOrder(Trade $trade): array
    {
        $ids = collect([$trade->from_character_id, $trade->to_character_id])->sort()->values();

        $locked = Character::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return [
            $locked[$trade->from_character_id],
            $locked[$trade->to_character_id],
        ];
    }

    /** @param  Collection<int,TradeItem>  $items */
    private function assertCanDeliver(Character $character, Collection $items, int $cp): void
    {
        if ($character->purseValue() < $cp) {
            throw MarketException::notEnoughCoins($cp, $character->purseValue());
        }

        foreach ($items as $item) {
            if (! $character->ownsItem($item->name, $item->qty)) {
                throw new MarketException(
                    "{$character->name} non ha più {$item->qty}× {$item->name}: lo scambio non è più valido."
                );
            }
        }
    }

    /** @param  Collection<int,TradeItem>  $items */
    private function move(Collection $items, Character $from, Character $to): void
    {
        foreach ($items as $item) {
            $from->removeFromInventory($item->name, $item->qty);

            $to->addToInventory(
                name: $item->name,
                qty: $item->qty,
                category: $item->category,
                valueCp: $item->value_cp,
                details: $item->details,
            );
        }
    }
}
