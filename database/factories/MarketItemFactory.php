<?php

namespace Database\Factories;

use App\Models\MarketItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketItem>
 */
class MarketItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Pozione di Cura',
            'category' => 'Pozioni',
            'price_cp' => 5000,
            'is_unlimited' => false,
            'stock' => 10,
            'details' => 'Recupera 2d4+2 punti ferita.',
        ];
    }

    /** Il prezzo in mo. */
    public function named(string $name, ?int $price = null): static
    {
        return $this->state(fn () => array_filter([
            'name' => $name,
            'price_cp' => $price === null ? null : $price * 100,
        ], fn ($v) => $v !== null));
    }

    /** Scorte infinite: il negozio non le esaurisce mai. */
    public function unlimited(): static
    {
        return $this->state(fn () => ['is_unlimited' => true, 'stock' => 0]);
    }

    public function stock(int $qty): static
    {
        return $this->state(fn () => ['is_unlimited' => false, 'stock' => $qty]);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['is_unlimited' => false, 'stock' => 0]);
    }
}
