<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Npc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Npc>
 */
class NpcFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => 'Berta la Locandiera',
            'location' => 'Locanda del Cinghiale, Valcupa',
            'wants' => 'Ritrovare il figlio scomparso',
        ];
    }
}
