<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $title = fake('it_IT')->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake('it_IT')->paragraph(),
            'quest_giver' => fake('it_IT')->firstName().' il Capogilda',
            'dm_id' => User::factory()->dm(),
            'created_by' => null,
            'ended_at' => null,
        ];
    }

    /** Campagna conclusa: non accetta più quest né richieste. */
    public function ended(): static
    {
        return $this->state(fn () => ['ended_at' => now()]);
    }

    public function runBy(User $dm): static
    {
        return $this->state(fn () => ['dm_id' => $dm->getKey()]);
    }
}
