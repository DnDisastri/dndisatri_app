<?php

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\SessionTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SessionTable> */
class SessionTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'name' => 'Tavolo '.fake()->numberBetween(1, 9),
            'seats' => 5,
            'position' => 0,
        ];
    }
}
