<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;

beforeEach(function () {
    $this->pg = Character::factory()->for(User::factory()->player())->create([
        'story' => 'Cresciuta fra i boschi del nord.',
        'private_story' => 'Ha tradito il suo vecchio ordine.',
    ]);
});

it('la storia privata la vedono il proprietario e i DM', function () {
    foreach ([$this->pg->user, User::factory()->dm()->create()] as $lettore) {
        $this->actingAs($lettore)->get(route('characters.section', [$this->pg, 'storia']))
            ->assertOk()
            ->assertSee('Cresciuta fra i boschi del nord.')
            ->assertSee('Ha tradito il suo vecchio ordine.');
    }
});

it('un altro giocatore vede solo la storia pubblica', function () {
    $this->actingAs(User::factory()->player()->create())->get(route('characters.section', [$this->pg, 'storia']))
        ->assertOk()
        ->assertSee('Cresciuta fra i boschi del nord.')
        ->assertDontSee('Ha tradito il suo vecchio ordine.')
        ->assertDontSee('Storia privata');
});

it('si propone con la modifica della scheda e entra con l\'approvazione', function () {
    $this->actingAs($this->pg->user)->post(route('proposals.edit', $this->pg), [
        'name' => $this->pg->name,
        'story' => $this->pg->story,
        'private_story' => 'Ha un fratello gemello nella Gilda dei Ladri.',
    ])->assertRedirect();

    $richiesta = PendingChange::where('character_id', $this->pg->id)->latest('id')->firstOrFail();
    expect($richiesta->diff)->toBe(['private_story' => 'Ha un fratello gemello nella Gilda dei Ladri.']);

    app(ApprovePendingChange::class)->handle($richiesta, User::factory()->dm()->create());

    expect($this->pg->fresh()->private_story)->toBe('Ha un fratello gemello nella Gilda dei Ladri.');
});
