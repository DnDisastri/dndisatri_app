<?php

declare(strict_types=1);

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

// Un campo disabilitato nell'interfaccia non è autorizzazione: l'ownership viene verificata anche lato server.
describe('creando una campagna', function () {
    it('un DM se la intesta comunque, anche indicando un altro', function () {
        $dm = User::factory()->dm()->create();
        $altro = User::factory()->dm()->create();

        $this->actingAs($dm);

        Livewire::test(CreateCampaign::class)
            ->fillForm([
                'title' => 'Le Rovine di Kaerdan',
                'slug' => 'rovine-di-kaerdan',
                'dm_id' => $altro->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Campaign::first()->dm_id)->toBe($dm->id)
            ->and(Campaign::first()->created_by)->toBe($dm->id);
    });

    it('un admin invece può intestarla a chi vuole', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateCampaign::class)
            ->fillForm([
                'title' => 'La Cripta Sommersa',
                'slug' => 'cripta-sommersa',
                'dm_id' => $dm->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Campaign::first()->dm_id)->toBe($dm->id);
    });
});

describe('modificando una campagna', function () {
    it('un DM non se la può passare a un altro', function () {
        $owner = User::factory()->dm()->create();
        $altro = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();

        $this->actingAs($owner);

        Livewire::test(EditCampaign::class, ['record' => $campaign->getRouteKey()])
            ->fillForm(['dm_id' => $altro->id])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($campaign->fresh()->dm_id)->toBe($owner->id);
    });

    it('un admin sì', function () {
        $owner = User::factory()->dm()->create();
        $nuovo = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditCampaign::class, ['record' => $campaign->getRouteKey()])
            ->fillForm(['dm_id' => $nuovo->id])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($campaign->fresh()->dm_id)->toBe($nuovo->id);
    });
});
