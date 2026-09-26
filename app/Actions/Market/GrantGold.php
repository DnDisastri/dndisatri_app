<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\LedgerAction;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Oro assegnato o tolto da un DM, senza passare da una richiesta.
 *
 * Il delta si applica **rileggendo** il saldo corrente sotto blocco, non
 * sovrascrivendo un valore letto prima: è la stessa regola che il brief chiede
 * per i bottini (§4.3), e vale per ogni movimento d'oro.
 *
 * Il saldo non scende sotto zero: un personaggio non va in debito.
 */
final class GrantGold
{
    public function handle(Character $character, int $amount, User $actor, ?string $reason = null): Character
    {
        return DB::transaction(function () use ($character, $amount, $actor, $reason) {
            $target = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();

            $applied = max($amount, -$target->gp);

            $target->increment('gp', $applied);
            $target->refresh();

            $verb = $applied >= 0 ? 'Assegnati' : 'Sottratti';
            $message = $reason !== null
                ? "{$verb} ".abs($applied)." mo — {$reason}"
                : "{$verb} ".abs($applied).' mo dal DM';

            $target->recordInLedger(LedgerAction::DmGold, $message, $applied, $actor);

            return $target;
        });
    }
}
