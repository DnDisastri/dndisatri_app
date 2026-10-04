<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Domain\Dnd\Coin;
use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * L'unico posto che scrive le pile di monete. Il personaggio va passato già
 * bloccato (`lockForUpdate`) da chi chiama, dentro la sua transazione.
 * Ogni metodo restituisce il movimento applicato, da dare al Registro.
 */
final class Purse
{
    /** Valore in rame; il resto torna in monete inferiori. */
    public function pay(Character $character, int $cp): Coins
    {
        try {
            $delta = $character->coins()->payment($cp);
        } catch (InvalidArgumentException) {
            throw MarketException::notEnoughCoins($cp, $character->purseValue());
        }

        return $this->apply($character, $delta);
    }

    public function receive(Character $character, Coins $coins): Coins
    {
        return $this->apply($character, $coins);
    }

    /** Vendite e scambi arrivano in oro, argento e rame. */
    public function receiveValue(Character $character, int $cp): Coins
    {
        return $this->apply($character, Coins::fromValue($cp));
    }

    /** Le monete esatte se ci sono, altrimenti il loro valore pagato col resto. */
    public function take(Character $character, Coins $coins): Coins
    {
        if (! $character->coins()->plus($coins->negate())->hasNegative()) {
            return $this->apply($character, $coins->negate());
        }

        return $this->pay($character, $coins->value());
    }

    /** Annulla un movimento del Registro: le monete esatte se possibile, altrimenti il valore. */
    public function revert(Character $character, Coins $delta): Coins
    {
        $back = $delta->negate();

        if (! $character->coins()->plus($back)->hasNegative()) {
            return $this->apply($character, $back);
        }

        return $back->value() >= 0
            ? $this->receiveValue($character, $back->value())
            : $this->pay($character, -$back->value());
    }

    /** Un cambio esatto, chiesto dalla scheda: finisce nel Registro a valore zero. */
    public function convert(Character $character, Coin $from, Coin $to, int $qty, ?User $actor = null): Coins
    {
        return DB::transaction(function () use ($character, $from, $to, $qty, $actor) {
            $locked = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();

            try {
                $delta = $locked->coins()->conversion($from, $to, $qty);
            } catch (InvalidArgumentException $e) {
                throw new MarketException($e->getMessage());
            }

            $this->apply($locked, $delta);

            $locked->recordInLedger(
                LedgerAction::Exchange,
                'Cambiate '.Coins::of($from, $qty)->format().' in '.Coins::of($to, $delta->get($to))->format(),
                $delta,
                $actor,
            );

            return $delta;
        });
    }

    private function apply(Character $character, Coins $delta): Coins
    {
        $after = $character->coins()->plus($delta);

        if ($after->hasNegative()) {
            throw MarketException::notEnoughCoins(abs($delta->value()), $character->purseValue());
        }

        if (max($after->toArray()) > Coins::MAX) {
            throw new MarketException('Troppe monete in una pila: cambiale prima in monete più grandi.');
        }

        $character->forceFill($after->toArray())->save();

        return $delta;
    }
}
