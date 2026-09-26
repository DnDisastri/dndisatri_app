<?php

namespace Database\Factories;

use App\Enums\TradeDirection;
use App\Models\Trade;
use App\Models\TradeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TradeItem>
 */
class TradeItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trade_id' => Trade::factory(),
            'direction' => TradeDirection::Give,
            'name' => 'Pozione di Cura',
            'category' => 'Pozioni',
            'qty' => 1,
            'value' => 50,
            'details' => null,
        ];
    }
}
