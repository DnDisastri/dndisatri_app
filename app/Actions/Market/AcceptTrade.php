<?php

declare(strict_types=1);

namespace App\Actions\Market;

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
 * Accettazione di uno scambio.
 *
 * A differenza degli annunci, negli scambi niente esce dall'inventario al
 * momento della proposta: la disponibilità si verifica **adesso**, e per
 * **entrambe** le parti (§4.8 del brief).
 *
 * Il che significa che una proposta può fallire qui perché nel frattempo chi
 * l'ha fatta ha venduto l'oggetto. È corretto, e il messaggio lo dice.
 *
 * L'ordine conta: prima si verifica tutto, poi si sposta tutto. Una verifica
 * fatta a metà strada lascerebbe uno dei due senza la sua parte.
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

            // Si bloccano sempre nello stesso ordine (per chiave crescente):
            // due scambi incrociati fra le stesse due persone, accettati nello
            // stesso istante, si bloccherebbero a vicenda in un abbraccio
            // mortale se l'ordine dipendesse dal ruolo.
            [$from, $to] = $this->lockBothInStableOrder($locked);

            $given = $locked->givenItems();
            $wanted = $locked->wantedItems();

            $this->assertCanDeliver($from, $given, $locked->give_gp);
            $this->assertCanDeliver($to, $wanted, $locked->want_gp);

            $this->move($given, from: $from, to: $to);
            $this->move($wanted, from: $to, to: $from);

            if ($locked->give_gp > 0) {
                $from->decrement('gp', $locked->give_gp);
                $to->increment('gp', $locked->give_gp);
            }

            if ($locked->want_gp > 0) {
                $to->decrement('gp', $locked->want_gp);
                $from->increment('gp', $locked->want_gp);
            }

            $locked->forceFill([
                'status' => TradeStatus::Accepted,
                'resolved_at' => now(),
            ])->save();

            $fromDelta = $locked->want_gp - $locked->give_gp;

            $from->refresh()->recordInLedger(
                LedgerAction::Trade,
                "Scambio con {$to->name}",
                $fromDelta,
                $actor,
            );

            $to->refresh()->recordInLedger(
                LedgerAction::Trade,
                "Scambio con {$from->name}",
                -$fromDelta,
                $actor,
            );

            // Avvisa chi ha proposto: chi accetta sa già di aver accettato.
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
    private function assertCanDeliver(Character $character, Collection $items, int $gp): void
    {
        if ($character->gp < $gp) {
            throw MarketException::notEnoughGold($gp, $character->gp);
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
                value: $item->value,
                details: $item->details,
            );
        }
    }
}
