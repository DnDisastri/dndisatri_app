<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Models\Character;
use App\Models\User;

// La storia è pubblica; le note del personaggio restano private.
describe('scriverla', function () {
    it('si scrive alla creazione, senza chiedere niente a nessuno', function () {
        $character = Character::factory()->create([
            'story' => 'Fabbro di villaggio finché i banditi non hanno bruciato la forgia.',
        ]);

        expect($character->fresh()->story)->toContain('Fabbro di villaggio');
    });

    it('ma cambiarla dopo passa da un DM, come il resto della scheda', function () {
        $character = Character::factory()->create(['story' => 'Prima versione.']);

        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['story' => 'Seconda versione.'],
        );

        expect($character->fresh()->story)->toBe('Prima versione.');

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->story)->toBe('Seconda versione.');
    });

    it('e rimandare la stessa storia non produce una richiesta', function () {
        $character = Character::factory()->create(['story' => 'Immutata.']);

        expect(fn () => app(ProposeChange::class)->edit(
            $character, $character->user, ['story' => 'Immutata.'],
        ))->toThrow(InvalidArgumentException::class, 'Non hai cambiato niente');
    });
});

describe('dal modulo', function () {
    it('il campo c\'è, con scritto che lo leggono gli altri', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->get(route('proposals.edit', $character))
            ->assertOk()
            ->assertSee('Storia')
            ->assertSee('La leggono gli altri giocatori');
    });

    it('e un testo troppo lungo viene rifiutato', function () {
        $character = Character::factory()->create();

        $this->actingAs($character->user)
            ->post(route('proposals.edit', $character), [
                'name' => $character->name,
                'story' => str_repeat('a', 2001),
            ])
            ->assertSessionHasErrors('story');
    });
});
