<?php

declare(strict_types=1);

use App\Filament\Resources\Monsters\MonsterResource;
use App\Filament\Resources\Monsters\Pages\CreateMonster;
use App\Models\Monster;
use App\Models\User;
use Livewire\Livewire;

// Il bestiario è condiviso tra i DM e non è accessibile ai giocatori.
describe('la sezione', function () {
    it('si apre per i DM', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(MonsterResource::getUrl('index'))
            ->assertOk();
    });

    it('e per gli admin', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(MonsterResource::getUrl('index'))
            ->assertOk();
    });

    it('ma non per i giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(MonsterResource::getUrl('index'))
            ->assertForbidden();
    });
});

describe('scrivere un mostro', function () {
    it('col suo essenziale, e chi l\'ha scritto resta scritto', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm);

        Livewire::test(CreateMonster::class)
            ->fillForm(['name' => 'Goblin', 'hp' => 7, 'ac' => 15])
            ->call('create')
            ->assertHasNoFormErrors();

        $goblin = Monster::where('name', 'Goblin')->firstOrFail();

        expect($goblin->hp)->toBe(7)
            ->and($goblin->ac)->toBe(15)
            ->and($goblin->created_by)->toBe($dm->id);
    });

    it('un altro DM lo può correggere: il bestiario è comune', function () {
        $altrui = Monster::factory()->create(['created_by' => User::factory()->dm()->create()->id]);

        $this->actingAs(User::factory()->dm()->create())
            ->get(MonsterResource::getUrl('edit', ['record' => $altrui]))
            ->assertOk();
    });
});
