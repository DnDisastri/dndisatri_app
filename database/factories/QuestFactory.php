<?php

namespace Database\Factories;

use App\Enums\QuestDifficulty;
use App\Enums\QuestType;
use App\Models\Campaign;
use App\Models\Quest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quest>
 */
class QuestFactory extends Factory
{
    public function definition(): array
    {
        $title = fake('it_IT')->sentence(4);

        return [
            'campaign_id' => Campaign::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake('it_IT')->paragraph(),
            'setting' => null,
            'rewards' => null,
            'difficulty' => fake()->randomElement(QuestDifficulty::cases()),
            'type' => QuestType::Campaign,
            'max_participants' => 4,
            'completed_at' => null,
            'closed_at' => null,
        ];
    }

    public function inCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => ['campaign_id' => $campaign->getKey()]);
    }

    public function slots(int $max): static
    {
        return $this->state(fn () => ['max_participants' => $max]);
    }

    /** Andata a buon fine. */
    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }

    /** Abbandonata: finisce anch'essa nel Libro Mastro, ma è un'altra cosa. */
    public function closed(): static
    {
        return $this->state(fn () => ['closed_at' => now()]);
    }
}
