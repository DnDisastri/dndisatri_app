<?php

declare(strict_types=1);

use App\Actions\Supervision\RejectSupervisedAction;
use App\Actions\Supervision\Supervisor;
use App\Actions\Users\IssueWarning;
use App\Actions\Users\LiftWarning;
use App\Models\Character;
use App\Models\User;


beforeEach(function () {
    $this->dm = User::factory()->dm()->create();

    $this->sorvegliato = User::factory()->player()->create();
    $this->anna = Character::factory()->ownedBy($this->sorvegliato)->create(['name' => 'Anna', 'gp' => 100]);

    app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Ha venduto due volte lo stesso anello.');
});

function inVendita(): App\Models\SupervisedAction
{
    test()->anna->addToInventory('Spada Lunga', value: 15);

    return app(Supervisor::class)->createListing(
        test()->sorvegliato, test()->anna, 'Spada Lunga', 1, 40,
    );
}

describe('chi ci entra', function () {
    it('chi è sotto richiamo, anche senza ancora aver chiesto niente', function () {
        $this->actingAs($this->sorvegliato)
            ->get(route('market.supervision'))
            ->assertOk()
            ->assertSee('Sei sotto richiamo')
            ->assertSee('Non hai niente in attesa');
    });
// Lo storico resta consultabile anche dopo la revoca del richiamo, così le decisioni precedenti non vanno perse.
    it('e chi lo è stato, per rileggere com\'è andata', function () {
        inVendita();
        app(LiftWarning::class)->handle($this->sorvegliato->warnings()->first(), $this->dm);

        $this->actingAs($this->sorvegliato)
            ->get(route('market.supervision'))
            ->assertOk()
            ->assertSee('Il richiamo è stato tolto');
    });

    it('ma non chi con la vigilanza non c\'entra niente', function () {
        $pulito = User::factory()->player()->create();

        $this->actingAs($pulito)
            ->get(route('market.supervision'))
            ->assertNotFound();
    });
});

describe('cosa mostra', function () {
    it('l\'azione in attesa, sciolta in righe leggibili', function () {
        inVendita();

        $this->actingAs($this->sorvegliato)
            ->get(route('market.supervision'))
            ->assertOk()
            ->assertSee('Messa in vendita')
            ->assertSee('In attesa')
            ->assertSee('Anna')
            ->assertSee('1× Spada Lunga')
            ->assertSee('40 mo');
    });
// Per un'azione rifiutata il motivo del DM fa parte della decisione e deve restare visibile al giocatore.
    it('e di una bloccata, il motivo scritto dal DM', function () {
        $azione = inVendita();
        app(RejectSupervisedAction::class)->handle($azione, $this->dm, 'Quaranta monete per una spada da quindici.');

        $this->actingAs($this->sorvegliato)
            ->get(route('market.supervision'))
            ->assertOk()
            ->assertSee('Rifiutata')
            ->assertSee('Perché è stata bloccata')
            ->assertSee('Quaranta monete per una spada da quindici.')
            ->assertSee('Puoi riproporla dal mercato');
    });
// La vigilanza è privata: ogni giocatore può vedere soltanto le proprie azioni supervisionate.
    it('e non le azioni di un altro', function () {
        inVendita();

        $altro = User::factory()->player()->create();
        app(IssueWarning::class)->handle($altro, $this->dm, 'Motivo suo.');
        $suo = Character::factory()->ownedBy($altro)->create(['name' => 'Bruno']);
        $suo->addToInventory('Corda di Seta', value: 1);
        app(Supervisor::class)->createListing($altro, $suo, 'Corda di Seta', 1, 5);

        $this->actingAs($this->sorvegliato)
            ->get(route('market.supervision'))
            ->assertOk()
            ->assertDontSee('Corda di Seta');
    });
});

describe('come ci si arriva', function () {
    it('dal richiamo nel profilo', function () {
        $this->actingAs($this->sorvegliato)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(route('market.supervision'), false);
    });

    it('ma il profilo di chi non è sotto richiamo non ce lo manda', function () {
        $pulito = User::factory()->player()->create();

        $this->actingAs($pulito)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee(route('market.supervision'), false);
    });
});
