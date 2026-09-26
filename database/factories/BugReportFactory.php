<?php

namespace Database\Factories;

use App\Enums\BugReportStatus;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReport>
 */
class BugReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->player(),
            'title' => 'Il pulsante per vendere non fa niente',
            'description' => 'Ho aperto il mercato, scelto un oggetto e premuto Vendi, ma non succede nulla.',
            'status' => BugReportStatus::Open,
            'page' => 'https://dndisastri.test/mercato',
            'user_agent' => 'Mozilla/5.0 (Android 14; Mobile)',
        ];
    }

    public function from(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->getKey()]);
    }

    public function closed(BugReportStatus $esito = BugReportStatus::Fixed): static
    {
        return $this->state(fn () => [
            'status' => $esito,
            'closed_at' => now(),
        ]);
    }
}
