<?php

declare(strict_types=1);

use App\Filament\Resources\TutorialSteps\TutorialStepResource;
use App\Models\TutorialStep;
use App\Models\User;

describe('il tutorial', function () {
    it('lo scrivono solo gli admin', function () {
        $passo = TutorialStep::factory()->create();

        expect(User::factory()->admin()->create()->can('create', TutorialStep::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', TutorialStep::class))->toBeFalse()
            ->and(User::factory()->player()->create()->can('create', TutorialStep::class))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('update', $passo))->toBeFalse();
    });

    it('la scope published lascia fuori le bozze', function () {
        TutorialStep::factory()->count(2)->create();
        TutorialStep::factory()->draft()->create();

        expect(TutorialStep::published()->count())->toBe(2);
    });
});

describe('la sezione del tutorial nel pannello', function () {
    it('si apre agli admin', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(TutorialStepResource::getUrl('index'))
            ->assertOk();
    });

    it('resta chiusa ai DM e ai giocatori', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(TutorialStepResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->player()->create())
            ->get(TutorialStepResource::getUrl('index'))
            ->assertForbidden();
    });
});
