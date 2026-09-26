<?php

declare(strict_types=1);

use App\Filament\Resources\Builds\BuildResource;
use App\Filament\Resources\Builds\Pages\CreateBuild;
use App\Models\Build;
use App\Models\User;
use Livewire\Livewire;
// I DM possono gestire le build consigliate; news ed eventi restano riservati agli admin.
describe('la sezione', function () {
    it('si apre per i DM', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(BuildResource::getUrl('index'))
            ->assertOk();
    });

    it('e per gli admin', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(BuildResource::getUrl('index'))
            ->assertOk();
    });

    it('ma non per i giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(BuildResource::getUrl('index'))
            ->assertForbidden();
    });
});

describe('scriverne una', function () {
    it('il modulo si apre', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(BuildResource::getUrl('create'))
            ->assertOk();
    });

    it('e chi la scrive resta scritto, senza essere un campo del modulo', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm);

        Livewire::test(CreateBuild::class)
            ->fillForm([
                'title' => 'Ranger Cacciatore',
                'slug' => 'ranger-cacciatore',
                'class' => 'Ranger',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Build::where('slug', 'ranger-cacciatore')->first()->created_by)->toBe($dm->id);
    });

    it('completa fino ai punteggi e alle abilità', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm);

        Livewire::test(CreateBuild::class)
            ->fillForm([
                'title' => 'Chierico di Ferro',
                'slug' => 'chierico-di-ferro',
                'class' => 'Chierico',
                'species' => 'Nano',
                'background' => 'Soldato',
                'scores' => ['str' => 14, 'dex' => 10, 'con' => 14, 'int' => 8, 'wis' => 15, 'cha' => 12],
                'skills' => ['medicine', 'religion'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $build = Build::where('slug', 'chierico-di-ferro')->firstOrFail();

        expect($build->isComplete())->toBeTrue()
            ->and($build->scores['wis'])->toBe(15);
    });
});

describe('modificarne una', function () {
    it('una ereditata la apre qualsiasi DM: è del gruppo', function () {
        $ereditata = Build::where('slug', 'guerriero-campione')->firstOrFail();

        $this->actingAs(User::factory()->dm()->create())
            ->get(BuildResource::getUrl('edit', ['record' => $ereditata]))
            ->assertOk();
    });

    it('quella di un altro DM no', function () {
        $altrui = Build::factory()->writtenBy(User::factory()->dm()->create())->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(BuildResource::getUrl('edit', ['record' => $altrui]))
            ->assertForbidden();
    });
});
