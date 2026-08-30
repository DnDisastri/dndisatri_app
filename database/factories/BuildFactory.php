<?php

namespace Database\Factories;

use App\Models\Build;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Build>
 */
class BuildFactory extends Factory
{
    /**
     * Nasce come le otto ereditate dalla vecchia applicazione: **incompleta**.
     * Classe, sottoclasse e un consiglio a parole, e nient'altro — che è la
     * situazione da cui parte davvero il gruppo.
     */
    public function definition(): array
    {
        $title = 'Guerriero '.fake('it_IT')->firstName();

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'tag' => 'Semplice · Robusto',
            'summary' => fake('it_IT')->sentence(12),
            'abilities_advice' => 'FOR e COS',
            'class' => 'Guerriero',
            'subclass' => 'Campione',
            'created_by' => null,
            'published_at' => now()->subDay(),
        ];
    }

    /** Compilata fino in fondo: chi la usa non deve scegliere niente. */
    public function complete(): static
    {
        return $this->state(fn () => [
            'species' => 'Nano',
            'background' => 'Soldato',
            'scores' => ['str' => 15, 'dex' => 13, 'con' => 14, 'int' => 8, 'wis' => 12, 'cha' => 10],
            'skills' => ['athletics', 'perception'],
            'equipment' => [0 => 0, 1 => 0],
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function writtenBy(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->getKey()]);
    }
}
