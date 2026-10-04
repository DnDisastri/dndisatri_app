<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RejectPendingChange;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;

beforeEach(function () {
    $this->dm = User::factory()->dm()->create();
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create(['level' => 1, 'str' => 10, 'dex' => 14]);
});

// Un passaggio di livello porta nel diff tutte le caratteristiche, anche quelle che non cambiano.
it('distingue i valori che cambiano da quelli che restano uguali', function () {
    $change = PendingChange::factory()->forCharacter($this->pg)->levelUp(2)->create([
        'diff' => ['level' => 2, 'str' => 10, 'dex' => 15],
    ]);

    $righe = $change->diffRows()->keyBy('label');

    expect($righe['Livello']['changed'])->toBeTrue()
        ->and($righe['Forza']['changed'])->toBeFalse()
        ->and($righe['Destrezza']['changed'])->toBeTrue();
});

it('all\'approvazione salva com\'era la scheda, e il confronto resta vero dopo', function () {
    $change = PendingChange::factory()->forCharacter($this->pg)->levelUp(2)->create([
        'diff' => ['level' => 2, 'str' => 10],
    ]);

    app(ApprovePendingChange::class)->handle($change, $this->dm);

    $deciso = $change->fresh();
    $righe = $deciso->diffRows()->keyBy('label');

    expect($deciso->before)->toBe(['level' => 1, 'str' => 10])
        ->and($this->pg->fresh()->level)->toBe(2)
        ->and($righe['Livello']['before'])->toBe('1')
        ->and($righe['Livello']['changed'])->toBeTrue()
        ->and($righe['Forza']['changed'])->toBeFalse()
        ->and($deciso->isStale())->toBeFalse();
});

it('anche al rifiuto, perché la scheda può cambiare dopo', function () {
    $change = app(ProposeChange::class)->edit($this->pg, $this->giocatore, ['notes' => 'Nuove note']);
    $this->pg->forceFill(['notes' => null])->save();

    app(RejectPendingChange::class)->handle($change, $this->dm);

    $this->pg->forceFill(['notes' => 'Scritte dopo'])->save();

    expect($change->fresh()->diffRows()->first()['before'])->toBe('Vuoto');
});

it('per le richieste decise senza copia mostra solo il dopo, con un avviso', function () {
    $change = PendingChange::factory()->forCharacter($this->pg)->levelUp(2)->create();
    app(ApprovePendingChange::class)->handle($change, $this->dm);
    $change->forceFill(['before' => null])->saveQuietly();

    expect($change->fresh()->lacksBefore())->toBeTrue()
        ->and($change->fresh()->diffRows()->first()['before'])->toBeNull();

    $this->actingAs($this->dm)
        ->get(PendingChangeResource::getUrl('view', ['record' => $change]))
        ->assertOk()
        ->assertSee('Non registrato')
        ->assertSee('si vede solo il')
        ->assertDontSee('La scheda è stata modificata dopo questa proposta');
});

describe('il comando che ricostruisce il prima', function () {
    beforeEach(function () {
        $this->change = PendingChange::factory()->forCharacter($this->pg)->levelUp(2)->create([
            'diff' => ['level' => 2, 'str' => 10],
        ]);
        app(ApprovePendingChange::class)->handle($this->change, $this->dm);
        $this->change->forceFill(['before' => null])->saveQuietly();
    });

    it('in prova non salva niente', function () {
        $this->artisan('dndisastri:ricostruisci-prima', ['--dry-run' => true])
            ->expectsOutputToContain('Prova')
            ->assertSuccessful();

        expect($this->change->fresh()->before)->toBeNull();
    });

    it('ricostruisce i valori vecchi dal log attività', function () {
        $this->artisan('dndisastri:ricostruisci-prima')->assertSuccessful();

        expect($this->change->fresh()->before)->toBe(['level' => 1, 'str' => 10]);
    });

    it('lascia stare una richiesta senza traccia nel log', function () {
        $this->change->forceFill(['reviewed_at' => now()->subYear()])->saveQuietly();

        $this->artisan('dndisastri:ricostruisci-prima')
            ->expectsOutputToContain('nessuna traccia nel log')
            ->assertSuccessful();

        expect($this->change->fresh()->before)->toBeNull();
    });
});
