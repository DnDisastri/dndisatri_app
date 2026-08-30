<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake('it_IT')->sentence(4);

        return [
            'created_by' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake('it_IT')->paragraph(),
            'cover_path' => null,
            'starts_at' => now()->addWeeks(2),
            'ends_at' => null,
            'location' => fake('it_IT')->city(),
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['starts_at' => now()->subMonth()]);
    }
}
