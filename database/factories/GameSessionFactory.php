<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\GameSession;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'number' => fake()->numberBetween(1, 40),
            'title' => fake('it_IT')->sentence(3),
            'played_at' => now()->subWeek(),
            'recap' => null,
            'recap_written_by' => null,
            'recap_written_at' => null,
        ];
    }

    public function inCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => ['campaign_id' => $campaign->getKey()]);
    }

    /** Sessione in calendario, non ancora giocata. */
    public function upcoming(): static
    {
        return $this->state(fn () => ['played_at' => now()->addWeek()]);
    }

    public function playedOn(DateTimeInterface|string $when): static
    {
        return $this->state(fn () => ['played_at' => $when]);
    }
}
