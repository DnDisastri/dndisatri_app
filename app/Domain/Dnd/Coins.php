<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use InvalidArgumentException;

/**
 * Un mucchio di monete: la borsa di un personaggio, o un movimento (allora le
 * pile possono essere negative). I prezzi non sono Coins: sono un valore in rame.
 */
final readonly class Coins
{
    /** Il massimo di `unsignedInteger`, il tipo di ogni pila e dei prezzi in rame. */
    public const MAX = 4_294_967_295;

    public function __construct(
        public int $pp = 0,
        public int $gp = 0,
        public int $sp = 0,
        public int $cp = 0,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    /** @param  array<string,mixed>|null  $piles */
    public static function fromArray(?array $piles): self
    {
        return new self(
            (int) ($piles['pp'] ?? 0),
            (int) ($piles['gp'] ?? 0),
            (int) ($piles['sp'] ?? 0),
            (int) ($piles['cp'] ?? 0),
        );
    }

    public static function of(Coin $coin, int $qty): self
    {
        return self::fromArray([$coin->value => $qty]);
    }

    /** Un valore scomposto in oro, argento e rame: il platino non si riceve come resto. */
    public static function fromValue(int $cp): self
    {
        if ($cp < 0) {
            throw new InvalidArgumentException('Un valore da scomporre non può essere negativo.');
        }

        return new self(0, intdiv($cp, 100), intdiv($cp % 100, 10), $cp % 10);
    }

    public function get(Coin $coin): int
    {
        return $this->{$coin->value};
    }

    /** Il valore in rame. */
    public function value(): int
    {
        return array_sum(array_map(fn (Coin $coin) => $this->get($coin) * $coin->value(), Coin::descending()));
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === ['pp' => 0, 'gp' => 0, 'sp' => 0, 'cp' => 0];
    }

    public function plus(self $other): self
    {
        return new self($this->pp + $other->pp, $this->gp + $other->gp, $this->sp + $other->sp, $this->cp + $other->cp);
    }

    public function negate(): self
    {
        return new self(-$this->pp, -$this->gp, -$this->sp, -$this->cp);
    }

    public function hasNegative(): bool
    {
        return min($this->toArray()) < 0;
    }

    /** @return array{pp: int, gp: int, sp: int, cp: int} */
    public function toArray(): array
    {
        return ['pp' => $this->pp, 'gp' => $this->gp, 'sp' => $this->sp, 'cp' => $this->cp];
    }

    /** @return array<string,int> solo le pile diverse da zero */
    public function nonZero(): array
    {
        return array_filter($this->toArray());
    }

    /** «3 mp 12 mo 5 ma»; le pile negative col loro segno. */
    public function format(): string
    {
        $parts = [];

        foreach (Coin::descending() as $coin) {
            if ($qty = $this->get($coin)) {
                $parts[] = ($qty < 0 ? '−' : '').number_format(abs($qty), 0, ',', '.').' '.$coin->abbreviation();
            }
        }

        return $parts === [] ? '0 mo' : implode(' ', $parts);
    }

    /** Un valore in rame scritto come prezzo: «7 mo 5 ma». */
    public static function formatValue(int $cp): string
    {
        return ($cp < 0 ? '−' : '').self::fromValue(abs($cp))->format();
    }

    /**
     * Il movimento con cui questa borsa paga un valore.
     *
     * Prima le monete esatte dalla più grande; se non bastano si spezza la moneta
     * più piccola rimasta, e il resto torna in monete inferiori. Dopo il primo
     * giro ogni moneta rimasta vale più del residuo, quindi una basta sempre.
     */
    public function payment(int $cp): self
    {
        if ($cp < 0) {
            throw new InvalidArgumentException('Non si paga un valore negativo.');
        }

        if ($this->value() < $cp) {
            throw new InvalidArgumentException('Le monete non bastano.');
        }

        $taken = [];
        $left = $this->toArray();
        $remaining = $cp;

        foreach (Coin::descending() as $coin) {
            $take = min($left[$coin->value], intdiv($remaining, $coin->value()));
            $taken[$coin->value] = $take;
            $left[$coin->value] -= $take;
            $remaining -= $take * $coin->value();
        }

        $delta = self::fromArray($taken)->negate();

        if ($remaining === 0) {
            return $delta;
        }

        foreach (array_reverse(Coin::descending()) as $coin) {
            if ($left[$coin->value] > 0) {
                return $delta->plus(self::of($coin, -1))->plus(self::fromValue($coin->value() - $remaining));
            }
        }

        throw new InvalidArgumentException('Le monete non bastano.');
    }

    /** Il movimento di un cambio esatto: `qty` monete `from` diventano monete `to`. */
    public function conversion(Coin $from, Coin $to, int $qty): self
    {
        if ($from === $to || $qty < 1) {
            throw new InvalidArgumentException('Scegli due monete diverse e una quantità.');
        }

        if ($this->get($from) < $qty) {
            throw new InvalidArgumentException("Hai solo {$this->get($from)} {$from->abbreviation()}.");
        }

        $value = $qty * $from->value();

        if ($value % $to->value() !== 0) {
            $step = intdiv($to->value(), $from->value());

            throw new InvalidArgumentException("Il cambio dev'essere esatto: {$from->abbreviation()} a multipli di {$step}.");
        }

        return self::of($from, -$qty)->plus(self::of($to, intdiv($value, $to->value())));
    }
}
