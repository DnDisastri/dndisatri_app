<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Monete date o tolte da un DM, senza richiesta. La borsa non scende mai sotto zero. */
final class GrantCoins
{
    public function give(Character $character, Coins $coins, User $actor, ?string $reason = null): Character
    {
        return $this->move($character, $coins, $actor, $reason, take: false);
    }

    /** Le monete esatte se ci sono, altrimenti il loro valore col resto. */
    public function take(Character $character, Coins $coins, User $actor, ?string $reason = null): Character
    {
        return $this->move($character, $coins, $actor, $reason, take: true);
    }

    private function move(Character $character, Coins $coins, User $actor, ?string $reason, bool $take): Character
    {
        if ($coins->isEmpty() || $coins->hasNegative()) {
            throw new MarketException('Indica quante monete.');
        }

        return DB::transaction(function () use ($character, $coins, $actor, $reason, $take) {
            $target = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            $purse = app(Purse::class);

            $delta = $take ? $purse->take($target, $coins) : $purse->receive($target, $coins);

            $verb = $take ? 'Tolte' : 'Assegnate';
            $message = "{$verb} ".$coins->format().($reason !== null ? " ({$reason})" : ' dal dungeon master');

            $target->recordInLedger(LedgerAction::DmGold, $message, $delta, $actor);

            return $target;
        });
    }
}
