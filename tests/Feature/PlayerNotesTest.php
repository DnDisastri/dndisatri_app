<?php

declare(strict_types=1);

use App\Livewire\PlayerNotes;
use App\Models\Character;
use App\Models\PlayerNote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->pg = Character::factory()->for(User::factory()->player())->create();
    $this->giocatore = $this->pg->user;
    $this->dm = User::factory()->dm()->create();
});

it('un DM scrive una nota e un altro DM la legge nella scheda', function () {
    Livewire::actingAs($this->dm)
        ->test(PlayerNotes::class, ['player' => $this->giocatore])
        ->set('testo', 'Ruola molto, lascia poco spazio agli altri.')
        ->call('aggiungi')
        ->assertHasNoErrors();

    $this->actingAs(User::factory()->dm()->create())
        ->get(route('characters.show', $this->pg))
        ->assertOk()
        ->assertSee('Ruola molto, lascia poco spazio agli altri.')
        ->assertSee($this->dm->name);
});

it('il giocatore e gli altri giocatori non la vedono', function () {
    $nota = new PlayerNote(['body' => 'Sta un po\' sulle sue.']);
    $nota->forceFill(['user_id' => $this->giocatore->id, 'author_id' => $this->dm->id])->save();

    foreach ([$this->giocatore, User::factory()->player()->create()] as $lettore) {
        $this->actingAs($lettore)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertDontSee('Sta un po\' sulle sue.')
            ->assertDontSee('Note sul giocatore');
    }

    Livewire::actingAs($this->giocatore)
        ->test(PlayerNotes::class, ['player' => $this->giocatore])
        ->assertForbidden();
});

it('la cancella solo chi l\'ha scritta, o un admin', function () {
    $nota = new PlayerNote(['body' => 'Arriva sempre in ritardo.']);
    $nota->forceFill(['user_id' => $this->giocatore->id, 'author_id' => $this->dm->id])->save();

    Livewire::actingAs(User::factory()->dm()->create())
        ->test(PlayerNotes::class, ['player' => $this->giocatore])
        ->call('elimina', $nota->id)
        ->assertForbidden();

    expect($nota->fresh())->not->toBeNull();

    Livewire::actingAs($this->dm)
        ->test(PlayerNotes::class, ['player' => $this->giocatore])
        ->call('elimina', $nota->id);

    expect($nota->fresh())->toBeNull();
});
