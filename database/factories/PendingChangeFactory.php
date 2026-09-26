<?php

namespace Database\Factories;

use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingChange>
 */
class PendingChangeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'requested_by' => User::factory()->player(),
            'type' => PendingChangeType::CharacterEdit,
            'diff' => ['notes' => 'Ho aggiunto due righe di background.'],
            'summary' => null,
            'grant_gp' => 0,
            'grant_items' => null,
            'base_updated_at' => now(),
            'status' => PendingChangeStatus::Pending,
        ];
    }

    /** Richiesta proposta dal proprietario del personaggio indicato. */
    public function forCharacter(Character $character): static
    {
        return $this->state(fn () => [
            'character_id' => $character->getKey(),
            'requested_by' => $character->user_id,
            'base_updated_at' => $character->updated_at,
        ]);
    }

    public function loot(int $gp = 50): static
    {
        return $this->state(fn () => [
            'type' => PendingChangeType::Loot,
            'diff' => null,
            'grant_gp' => $gp,
            'grant_items' => [['name' => 'Pozione di Cura', 'qty' => 1]],
            'summary' => "Bottino di sessione: {$gp} mo e una pozione.",
        ]);
    }

    public function levelUp(int $to = 2): static
    {
        return $this->state(fn () => [
            'type' => PendingChangeType::LevelUp,
            'diff' => ['level' => $to],
            'summary' => 'Livello '.($to - 1)." → {$to}.",
        ]);
    }

    public function approvedBy(User $reviewer): static
    {
        return $this->state(fn () => [
            'status' => PendingChangeStatus::Approved,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejectedBy(User $reviewer): static
    {
        return $this->state(fn () => [
            'status' => PendingChangeStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ]);
    }
}
