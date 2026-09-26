<?php

declare(strict_types=1);

use App\Enums\PendingChangeType;
use App\Models\PendingChange;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('serve la foto proposta a un DM che può esaminare la richiesta', function () {
    Storage::fake('local');
    Storage::disk('local')->put('proposte-foto/test.png', 'immagine-finta');

    $change = PendingChange::factory()->create([
        'type' => PendingChangeType::CharacterEdit,
        'diff' => ['photo_path' => 'proposte-foto/test.png'],
    ]);

    $this->actingAs(User::factory()->dm()->create())
        ->get(route('pending-changes.photo', $change))
        ->assertOk();
});

it('nega la foto a un giocatore estraneo', function () {
    Storage::fake('local');
    Storage::disk('local')->put('proposte-foto/test.png', 'x');

    $change = PendingChange::factory()->create([
        'diff' => ['photo_path' => 'proposte-foto/test.png'],
    ]);

    $this->actingAs(User::factory()->player()->create())
        ->get(route('pending-changes.photo', $change))
        ->assertForbidden();
});

it('risponde 404 se la richiesta non ha una foto', function () {
    $change = PendingChange::factory()->create(['diff' => ['story' => 'niente foto']]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('pending-changes.photo', $change))
        ->assertNotFound();
});

it('tiene la foto fuori dalle righe testuali del diff', function () {
    $change = PendingChange::factory()->create([
        'diff' => ['story' => 'nuova storia', 'photo_path' => 'proposte-foto/x.png'],
    ]);

    expect($change->diffRows()->pluck('label')->all())->toBe(['Storia'])
        ->and($change->proposedPhotoPath())->toBe('proposte-foto/x.png');
});
