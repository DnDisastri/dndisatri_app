<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake('it_IT')->sentence(5);

        return [
            'author_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'excerpt' => fake('it_IT')->sentence(12),
            'body' => fake('it_IT')->paragraphs(3, true),
            'cover_path' => null,
            'published_at' => now()->subDay(),
            'is_pinned' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    /** Pubblicazione programmata: scritta, ma non ancora visibile. */
    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
