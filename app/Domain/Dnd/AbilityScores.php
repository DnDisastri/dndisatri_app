<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * I sei punteggi di caratteristica.
 *
 * Esistono in due forme: i punteggi BASE (creazione + ASI, quelli salvati) e i
 * punteggi EFFICACI, che sono i base più gli effetti degli oggetti magici.
 * Ogni calcolo di gioco usa gli EFFICACI; i base non vengono mai modificati.
 */
final readonly class AbilityScores
{
    /** @var array<string,int> */
    private array $scores;

    /** @param array<string,int|string|null> $scores */
    public function __construct(array $scores = [])
    {
        $normalized = [];

        foreach (Ability::cases() as $ability) {
            // 10 è il punteggio "medio" e quindi il default innocuo:
            // dà modificatore 0 e non altera nessun calcolo.
            $normalized[$ability->value] = (int) ($scores[$ability->value] ?? 10);
        }

        $this->scores = $normalized;
    }

    /** @param array<string,int|string|null> $scores */
    public static function fromArray(array $scores): self
    {
        return new self($scores);
    }

    public function score(Ability $ability): int
    {
        return $this->scores[$ability->value];
    }

    public function modifier(Ability $ability): int
    {
        return Ability::modifierFor($this->scores[$ability->value]);
    }

    /**
     * Applica gli effetti degli oggetti magici e restituisce i punteggi
     * EFFICACI. L'istanza di partenza resta intatta.
     *
     * @param  iterable<ItemEffect>  $effects
     */
    public function withEffects(iterable $effects): self
    {
        $scores = $this->scores;

        foreach ($effects as $effect) {
            $scores[$effect->ability->value] = $effect->applyTo($scores[$effect->ability->value]);
        }

        return new self($scores);
    }

    /** @return array<string,int> */
    public function toArray(): array
    {
        return $this->scores;
    }
}
