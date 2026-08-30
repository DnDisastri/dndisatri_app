<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Effetto di un oggetto magico su una caratteristica.
 *
 * I punteggi base del personaggio non vengono mai toccati: gli effetti si
 * applicano sopra, così togliendo l'oggetto tutto torna com'era.
 */
final readonly class ItemEffect
{
    public function __construct(
        public Ability $ability,
        public ItemEffectMode $mode,
        public int $value,
        public string $name = '',
    ) {}

    /**
     * @param  array{ability: string, mode: string, value: int|string, name?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Ability::from($data['ability']),
            ItemEffectMode::from($data['mode']),
            (int) $data['value'],
            $data['name'] ?? '',
        );
    }

    /** Applica l'effetto a un punteggio, restituendo quello risultante. */
    public function applyTo(int $score): int
    {
        return match ($this->mode) {
            // "Il punteggio diventa X" vale solo se migliora: una Cintura di
            // Forza del Gigante non indebolisce chi è già più forte.
            ItemEffectMode::Set => max($score, $this->value),
            ItemEffectMode::Bonus => $score + $this->value,
        };
    }
}
