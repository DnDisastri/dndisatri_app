<?php

declare(strict_types=1);

use App\Enums\PendingChangeStatus;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

describe('modifiche al personaggio', function () {
    it('finiscono nel registro, con il valore prima e dopo', function () {
        $character = Character::factory()->create(['gp' => 100]);

        $character->update(['gp' => 250]);

        $activity = Activity::forSubject($character)->latest('id')->first();

// In Activitylog 5 il diff del modello è in `attribute_changes`; `properties` contiene solo dati aggiunti manualmente.
        expect($activity)->not->toBeNull()
            ->and($activity->log_name)->toBe('personaggio')
            ->and($activity->attribute_changes['old']['gp'])->toBe(100)
            ->and($activity->attribute_changes['attributes']['gp'])->toBe(250);
    });

    it('registrano chi le ha fatte', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create();

        $this->actingAs($dm);
        $character->update(['gp' => 500]);

        $activity = Activity::forSubject($character)->latest('id')->first();

        expect($activity->causer->is($dm))->toBeTrue();
    });

    it('non sporcano il registro quando non cambia nulla', function () {
        $character = Character::factory()->create(['gp' => 100]);
        $before = Activity::count();

        $character->update(['gp' => 100]);

        expect(Activity::count())->toBe($before);
    });
});

describe('decisioni sulle richieste', function () {
    it('lasciano traccia di chi ha approvato', function () {
        $dm = User::factory()->dm()->create();
        $change = PendingChange::factory()->create();

        $this->actingAs($dm);
// Stato e revisore non sono mass-assignable: una decisione deve passare dalla logica applicativa autorizzata.
        $change->status = PendingChangeStatus::Approved;
        $change->reviewed_by = $dm->getKey();
        $change->reviewed_at = now();
        $change->save();

        $activity = Activity::forSubject($change)->latest('id')->first();

        expect($activity->log_name)->toBe('richiesta')
            ->and($activity->causer->is($dm))->toBeTrue()
            ->and($activity->attribute_changes['attributes']['status'])->toBe('approved');
    });
});
