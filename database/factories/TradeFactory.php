<?php

namespace Database\Factories;

use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use App\Models\Character;
use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_character_id' => Character::factory(),
            'to_character_id' => Character::factory(),
            'give_cp' => 0,
            'want_cp' => 0,
            'status' => TradeStatus::Pending,
            'message' => null,
        ];
    }

    public function between(Character $from, Character $to): static
    {
        return $this->state(fn () => [
            'from_character_id' => $from->getKey(),
            'to_character_id' => $to->getKey(),
        ]);
    }

    /** Importi in mo. */
    public function gold(int $give = 0, int $want = 0): static
    {
        return $this->state(fn () => ['give_cp' => $give * 100, 'want_cp' => $want * 100]);
    }

    /** Aggiunge un oggetto offerto da chi propone. */
    public function giving(string $name, int $qty = 1, int $value = 0): static
    {
        return $this->hasItems(1, [
            'direction' => TradeDirection::Give,
            'name' => $name,
            'qty' => $qty,
            'value_cp' => $value * 100,
        ]);
    }

    /** Aggiunge un oggetto chiesto in cambio. */
    public function wanting(string $name, int $qty = 1, int $value = 0): static
    {
        return $this->hasItems(1, [
            'direction' => TradeDirection::Want,
            'name' => $name,
            'qty' => $qty,
            'value_cp' => $value * 100,
        ]);
    }
}
