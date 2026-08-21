<?php

declare(strict_types=1);

use App\Enums\LedgerAction;
use App\Livewire\DmTools;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;
use Livewire\Livewire;


beforeEach(function () {
    $this->dm = User::factory()->dm()->create();
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Anna', 'gp' => 100]);
});

describe('chi li vede', function () {
    it('chi conduce, su un personaggio vivo', function () {
        $this->actingAs($this->dm)
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertSee('Strumenti da DM')
            ->assertSee('Assegna oro')
            ->assertSee('Dichiara caduto');
    });

    it('ma non un giocatore qualunque', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.show', $this->pg))
            ->assertOk()
            ->assertDontSee('Strumenti da DM');
    });

    it('e su un caduto non compaiono a nessuno', function () {
        $morto = Character::factory()->fallen()->create();

        $this->actingAs($this->dm)
            ->get(route('characters.show', $morto))
            ->assertOk()
            ->assertDontSee('Strumenti da DM');
    });
});

describe('assegna oro', function () {
    it('lo aggiunge e lascia una riga nel Registro, col motivo', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('oroImporto', 50)
            ->set('oroMotivo', 'Premio di fine quest')
            ->call('assegnaOro')
            ->assertHasNoErrors();

        $this->pg->refresh();
        $riga = $this->pg->ledgerEntries()->latest('id')->first();

        expect($this->pg->gp)->toBe(150)
            ->and($riga->action)->toBe(LedgerAction::DmGold)
            ->and($riga->gp_delta)->toBe(50)
            ->and($riga->message)->toContain('Premio di fine quest');
    });

    it('anche in negativo, ma mai sotto zero', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('oroImporto', -1000)
            ->set('oroMotivo', 'Una multa salatissima')
            ->call('assegnaOro')
            ->assertHasNoErrors();

        expect($this->pg->refresh()->gp)->toBe(0);
    });

    it('il motivo è obbligatorio', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('oroImporto', 50)
            ->set('oroMotivo', '')
            ->call('assegnaOro')
            ->assertHasErrors('oroMotivo');

        expect($this->pg->refresh()->gp)->toBe(100);
    });

    it('e zero non assegna niente', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('oroImporto', 0)
            ->set('oroMotivo', 'Niente')
            ->call('assegnaOro')
            ->assertHasErrors('oroImporto');
    });

    it('un giocatore non può assegnarsi oro', function () {
        Livewire::actingAs($this->giocatore)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('oroImporto', 9999)
            ->set('oroMotivo', 'Vorrei')
            ->call('assegnaOro')
            ->assertForbidden();

        expect($this->pg->refresh()->gp)->toBe(100);
    });
});

describe('dichiara caduto', function () {
    it('lo segna morto, col racconto e la serata', function () {
        $campagna = Campaign::factory()->create(['title' => 'I Tre Regni']);
        $serata = GameSession::factory()->for($campagna)->create(['number' => 12, 'title' => 'La Torre Nera']);

        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('morteRacconto', 'Caduto dalla torre per salvare la bambina.')
            ->set('morteSessione', $serata->id)
            ->set('morteCapito', true)
            ->call('dichiaraCaduto')
            ->assertHasNoErrors()
            ->assertRedirect(route('characters.show', $this->pg));

        $this->pg->refresh();

        expect($this->pg->isAlive())->toBeFalse()
            ->and($this->pg->death_story)->toBe('Caduto dalla torre per salvare la bambina.')
            ->and($this->pg->died_in_session_id)->toBe($serata->id);
    });
// Racconto e serata sono opzionali perché una morte può essere registrata anche fuori da una sessione.
    it('e anche a mani vuote, purché confermato', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('morteCapito', true)
            ->call('dichiaraCaduto')
            ->assertHasNoErrors();

        expect($this->pg->refresh()->isAlive())->toBeFalse();
    });

    it('ma senza la conferma non muore', function () {
        Livewire::actingAs($this->dm)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('morteRacconto', 'Un incidente')
            ->set('morteCapito', false)
            ->call('dichiaraCaduto')
            ->assertHasErrors('morteCapito');

        expect($this->pg->refresh()->isAlive())->toBeTrue();
    });

    it('e un giocatore non può dichiarare caduto il suo personaggio', function () {
        Livewire::actingAs($this->giocatore)
            ->test(DmTools::class, ['character' => $this->pg])
            ->set('morteCapito', true)
            ->call('dichiaraCaduto')
            ->assertForbidden();

        expect($this->pg->refresh()->isAlive())->toBeTrue();
    });
});
