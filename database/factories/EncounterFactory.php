<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Encounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'title' => 'Imboscata sul ponte',
        ];
    }
}
