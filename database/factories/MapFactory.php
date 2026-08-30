<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Map;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Map>
 */
class MapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => null,
            'uploaded_by' => null,
            'title' => fake('it_IT')->sentence(3),
            'description' => null,
            'image_path' => 'maps/'.fake()->uuid().'.jpg',
        ];
    }

    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => ['campaign_id' => $campaign->getKey()]);
    }
}
