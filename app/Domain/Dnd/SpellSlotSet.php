<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Gli slot incantesimo disponibili a un dato livello.
 *
 * Il Pact Magic del Warlock ha la stessa forma degli altri (livello dello slot
 * => quantità), ma va tenuto distinto: si recupera anche con un riposo breve,
 * mentre gli slot normali solo col riposo lungo.
 */
final readonly class SpellSlotSet
{
    /** @param array<int,int> $slots livello dello slot => quantità */
    private function __construct(
        public array $slots,
        public bool $isPact,
    ) {}

    /** @param array<int,int> $slots */
    public static function standard(array $slots): self
    {
        return new self($slots, isPact: false);
    }

    public static function pact(int $count, int $spellLevel): self
    {
        return new self([$spellLevel => $count], isPact: true);
    }

    public static function none(): self
    {
        return new self([], isPact: false);
    }

    public function isEmpty(): bool
    {
        return $this->slots === [];
    }

    /** Il livello più alto di incantesimo lanciabile; 0 se non ne lancia. */
    public function maxSpellLevel(): int
    {
        return $this->isEmpty() ? 0 : max(array_keys($this->slots));
    }

    public function countAt(int $spellLevel): int
    {
        return $this->slots[$spellLevel] ?? 0;
    }

    public function total(): int
    {
        return array_sum($this->slots);
    }
}
