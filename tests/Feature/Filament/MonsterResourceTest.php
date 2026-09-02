<?php

declare(strict_types=1);

use App\Filament\Resources\Monsters\MonsterResource;
use App\Filament\Resources\Monsters\Pages\CreateMonster;
use App\Filament\Resources\Monsters\Pages\ListMonsters;
use App\Models\Campaign;
use App\Models\Monster;
use App\Models\User;
use Livewire\Livewire;

// I mostri pubblici sono condivisi tra i DM; quelli legati a una campagna li
// vede solo il suo DM. I giocatori non accedono al bestiario.
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

    it('un altro DM può correggere un mostro pubblico', function () {
        $altrui = Monster::factory()->create(['created_by' => User::factory()->dm()->create()->id]);

        $this->actingAs(User::factory()->dm()->create())
            ->get(MonsterResource::getUrl('edit', ['record' => $altrui]))
            ->assertOk();
    });

    it('lega il mostro alla campagna quando non è pubblico', function () {
        $dm = User::factory()->dm()->create();
        $tavolo = Campaign::factory()->create(['dm_id' => $dm->id]);

        Livewire::actingAs($dm)
            ->test(CreateMonster::class)
            ->fillForm([
                'name' => 'Guardiano', 'hp' => 30, 'ac' => 16,
                'pubblico' => false, 'campaign_id' => $tavolo->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Monster::where('name', 'Guardiano')->firstOrFail()->campaign_id)->toBe($tavolo->id);
    });
});

describe('i mostri legati a una campagna', function () {
    it('un DM vede i pubblici e i propri, non quelli di un altro tavolo', function () {
        $dm = User::factory()->dm()->create();
        $mio = Campaign::factory()->create(['dm_id' => $dm->id]);
        $altro = Campaign::factory()->create(['dm_id' => User::factory()->dm()->create()->id]);

        $pubblico = Monster::factory()->create(['campaign_id' => null]);
        $mioMostro = Monster::factory()->create(['campaign_id' => $mio->id]);
        $altruiMostro = Monster::factory()->create(['campaign_id' => $altro->id]);

        Livewire::actingAs($dm)
            ->test(ListMonsters::class)
            ->assertCanSeeTableRecords([$pubblico, $mioMostro])
            ->assertCanNotSeeTableRecords([$altruiMostro]);
    });

    it('non li lascia vedere né modificare a un DM di un altro tavolo', function () {
        $altro = Campaign::factory()->create(['dm_id' => User::factory()->dm()->create()->id]);
        $altruiMostro = Monster::factory()->create(['campaign_id' => $altro->id]);
        $estraneo = User::factory()->dm()->create();

        expect($estraneo->can('view', $altruiMostro))->toBeFalse()
            ->and($estraneo->can('update', $altruiMostro))->toBeFalse()
            ->and($estraneo->can('delete', $altruiMostro))->toBeFalse();
    });

    it('li lascia gestire al DM della campagna e agli admin', function () {
        $dm = User::factory()->dm()->create();
        $tavolo = Campaign::factory()->create(['dm_id' => $dm->id]);
        $mostro = Monster::factory()->create(['campaign_id' => $tavolo->id]);

        expect($dm->can('update', $mostro))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('update', $mostro))->toBeTrue();
    });
});
