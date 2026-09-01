<?php

declare(strict_types=1);

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

it('rende il form con lo slider dell\'opacità', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(CreateCampaign::class)
        ->assertFormFieldExists('background_opacity')
        ->assertOk();
});

it('salva l\'opacità del velo scelta', function () {
    $dm = User::factory()->dm()->create();

    Livewire::actingAs($dm)
        ->test(CreateCampaign::class)
        ->fillForm([
            'title' => 'Il Tavolo Test',
            'slug' => 'tavolo-test',
            'season' => 1,
            'background_opacity' => 40,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Campaign::where('slug', 'tavolo-test')->firstOrFail()->background_opacity)->toBe(40);
});
