<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Models\Character;
use App\Models\MarketListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketListing>
 */
class MarketListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_character_id' => Character::factory(),
            'name' => 'Pozione di Cura',
            'category' => 'Pozioni',
            'qty' => 1,
            'price' => 60,
            'unit_value' => 50,
            'details' => null,
            'status' => ListingStatus::Active,
        ];
    }

    public function soldBy(Character $seller): static
    {
        return $this->state(fn () => ['seller_character_id' => $seller->getKey()]);
    }

    public function of(string $name, int $qty = 1, int $price = 60): static
    {
        return $this->state(fn () => ['name' => $name, 'qty' => $qty, 'price' => $price]);
    }
}
