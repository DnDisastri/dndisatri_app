<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\CharacterPhoto;
use App\Actions\Characters\RejectPendingChange;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Le foto in attesa di approvazione restano sul disco privato e diventano pubbliche solo dopo l'approvazione.
beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

describe('caricarla', function () {
    it('mette il file al riparo e non tocca la scheda', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'photo' => UploadedFile::fake()->image('grimm.jpg'),
            ])
            ->assertRedirect();

        $change = $character->pendingChanges()->latest('id')->first();
        $pending = $change->diff['photo_path'];

        expect($pending)->toStartWith(CharacterPhoto::PENDING_DIR.'/')
            ->and(Storage::disk('local')->exists($pending))->toBeTrue()
            ->and(Storage::disk('public')->exists($pending))->toBeFalse()
            ->and($character->fresh()->photo_path)->toBeNull();
    });

    it('rifiuta quello che non è un\'immagine', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'photo' => UploadedFile::fake()->create('trappola.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');
    });

    it('e quello che pesa troppo', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'photo' => UploadedFile::fake()->image('enorme.jpg')->size(5000),
            ])
            ->assertSessionHasErrors('photo');
    });
});

describe('la decisione', function () {
    it('approvata, la foto diventa pubblica e sparisce dall\'attesa', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)->post(route('proposals.edit', $character), [
            'name' => $character->name,
            'photo' => UploadedFile::fake()->image('grimm.jpg'),
        ]);

        $change = $character->pendingChanges()->latest('id')->first();
        $pending = $change->diff['photo_path'];

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $published = $character->fresh()->photo_path;

        expect($published)->toStartWith('personaggi/'.$character->getKey().'/')
            ->and(Storage::disk('public')->exists($published))->toBeTrue()
            ->and(Storage::disk('local')->exists($pending))->toBeFalse()
            ->and($character->fresh()->photoUrl())->toContain($published);
    });

    it('rifiutata, il file viene buttato', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)->post(route('proposals.edit', $character), [
            'name' => $character->name,
            'photo' => UploadedFile::fake()->image('grimm.jpg'),
        ]);

        $change = $character->pendingChanges()->latest('id')->first();
        $pending = $change->diff['photo_path'];

        app(RejectPendingChange::class)->handle($change, User::factory()->dm()->create(), 'Non è tua.');

        expect(Storage::disk('local')->exists($pending))->toBeFalse()
            ->and($character->fresh()->photo_path)->toBeNull();
    });

    it('la foto di prima non resta sul disco a fare compagnia alla nuova', function () {
        $character = Character::factory()->create();
        $dm = User::factory()->dm()->create();

        $carica = function () use ($character) {
            $this->actingAs($character->user)->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'photo' => UploadedFile::fake()->image('ritratto.jpg'),
            ]);

            return $character->pendingChanges()->latest('id')->first();
        };

        app(ApprovePendingChange::class)->handle($carica(), $dm);
        $prima = $character->fresh()->photo_path;

        app(ApprovePendingChange::class)->handle($carica(), $dm);
        $dopo = $character->fresh()->photo_path;

        expect($dopo)->not->toBe($prima)
            ->and(Storage::disk('public')->exists($prima))->toBeFalse()
            ->and(Storage::disk('public')->exists($dopo))->toBeTrue();
    });

    it('e se il file è sparito nel frattempo, la scheda tiene quella che ha', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)->post(route('proposals.edit', $character), [
            'name' => $character->name,
            'photo' => UploadedFile::fake()->image('grimm.jpg'),
        ]);

        $change = $character->pendingChanges()->latest('id')->first();

        Storage::disk('local')->delete($change->diff['photo_path']);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->photo_path)->toBeNull();
    });
});
