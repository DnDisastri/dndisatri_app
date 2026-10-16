<?php

declare(strict_types=1);

use App\Enums\CreatureSize;
use App\Enums\CreatureType;
use App\Filament\Resources\DmRules\Pages\CreateDmRule;
use App\Livewire\CombatTracker;
use App\Models\Campaign;
use App\Models\DmRule;
use App\Models\Encounter;
use App\Models\Faq;
use App\Models\GameSession;
use App\Models\Monster;
use App\Models\TutorialStep;
use App\Models\User;
use Livewire\Livewire;

describe('il resoconto', function () {
    beforeEach(function () {
        $this->dm = User::factory()->dm()->create();
        $this->sessione = GameSession::factory()->create(['played_at' => now()->subDay()]);
    });

    it('si ferma a 3.600 caratteri, col contatore', function () {
        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('a', 3601)])
            ->assertSessionHasErrors('recap');

        $this->actingAs($this->dm)
            ->get(route('sessions.show', $this->sessione))
            ->assertSee('/ 3.600');
    });

    it('uno scritto prima del limite si corregge, ma non si allunga', function () {
        $this->sessione->forceFill(['recap' => str_repeat('a', 5000)])->save();

        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('b', 4800)])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('b', 5001)])
            ->assertSessionHasErrors('recap');
    });
});
