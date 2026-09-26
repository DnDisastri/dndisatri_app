<?php

declare(strict_types=1);

use App\Enums\PendingChangeStatus;
use App\Filament\Resources\PendingChanges\Pages\ViewPendingChange;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use Livewire\Livewire;

describe('la bacheca', function () {
    it('si apre per DM e admin, non per i giocatori', function () {
        PendingChange::factory()->count(3)->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(PendingChangeResource::getUrl('index'))->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(PendingChangeResource::getUrl('index'))->assertOk();

        $this->actingAs(User::factory()->player()->create())
            ->get(PendingChangeResource::getUrl('index'))->assertForbidden();
    });

    it('conta le richieste in attesa sulla voce di menu', function () {
        expect(PendingChangeResource::getNavigationBadge())->toBeNull();

        PendingChange::factory()->count(4)->create();

        expect(PendingChangeResource::getNavigationBadge())->toBe('4');
    });

    it('non ha pagine di creazione o modifica', function () {
        expect(array_keys(PendingChangeResource::getPages()))->toBe(['index', 'view']);
    });
});

describe('il confronto fra prima e dopo', function () {
    it('mostra il valore attuale accanto a quello proposto', function () {
        $character = Character::factory()->create(['background' => 'Accolito', 'level' => 3]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['background' => 'Criminale'],
        ]);

        $rows = $change->load('character')->diffRows();

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['label'])->toBe('Background')
            ->and($rows[0]['before'])->toBe('Accolito')
            ->and($rows[0]['after'])->toBe('Criminale');
    });

    it('rende leggibili i campi composti', function () {
        $character = Character::factory()->create(['skills' => []]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['skills' => ['stealth' => 'expert', 'arcana' => 'none']],
        ]);

        $rows = $change->load('character')->diffRows();
// I valori `none` rappresentano assenza di modifica e non devono essere mostrati nel diff.
        expect($rows[0]['label'])->toBe('Abilità')

            ->and($rows[0]['after'])->toBe('stealth: expert')
            ->and($rows[0]['before'])->toBe('Vuoto');
    });

    it('traduce i nomi delle caratteristiche', function () {
        $character = Character::factory()->create(['con' => 14]);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['con' => 16],
        ]);

        expect($change->load('character')->diffRows()[0]['label'])->toBe('Costituzione');
    });

    it('si apre la pagina di dettaglio', function () {
        $change = PendingChange::factory()->create();

        $this->actingAs(User::factory()->dm()->create())
            ->get(PendingChangeResource::getUrl('view', ['record' => $change]))
            ->assertOk();
    });
});

describe('decidere dalla bacheca', function () {
    it('approvando si applica la modifica e resta la firma', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['notes' => 'prima']);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['notes' => 'dopo'],
        ]);

        $this->actingAs($dm);

        Livewire::test(ViewPendingChange::class, ['record' => $change->getRouteKey()])
            ->callAction('approva', ['note' => 'ok']);

        expect($character->fresh()->notes)->toBe('dopo')
            ->and($change->fresh()->status)->toBe(PendingChangeStatus::Approved)
            ->and($change->fresh()->reviewed_by)->toBe($dm->id);
    });

    it('rifiutando non si tocca il personaggio', function () {
        $character = Character::factory()->create(['notes' => 'intatte']);
        $change = PendingChange::factory()->forCharacter($character)->create([
            'diff' => ['notes' => 'mai applicate'],
        ]);

        $this->actingAs(User::factory()->dm()->create());

        Livewire::test(ViewPendingChange::class, ['record' => $change->getRouteKey()])
            ->callAction('rifiuta', ['note' => 'no']);

        expect($character->fresh()->notes)->toBe('intatte')
            ->and($change->fresh()->status)->toBe(PendingChangeStatus::Rejected);
    });

    it('un DM non decide sulle richieste del proprio personaggio', function () {

        $dm = User::factory()->dm()->create();
        $change = PendingChange::factory()
            ->forCharacter(Character::factory()->ownedBy($dm)->create())
            ->create();

        $this->actingAs($dm);

        Livewire::test(ViewPendingChange::class, ['record' => $change->getRouteKey()])
            ->assertActionDisabled('approva')
            ->assertActionDisabled('rifiuta');

        expect($change->fresh()->isPending())->toBeTrue();
    });
});
