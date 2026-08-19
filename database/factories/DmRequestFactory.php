<?php

namespace Database\Factories;

use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DmRequest>
 */
class DmRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->player(),
            'message' => 'Vorrei condurre un tavolo per il gruppo.',
            'status' => PendingChangeStatus::Pending,
        ];
    }

    public function from(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->getKey()]);
    }
}
