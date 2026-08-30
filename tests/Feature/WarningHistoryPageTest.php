<?php

declare(strict_types=1);

use App\Actions\Users\IssueWarning;
use App\Actions\Users\LiftWarning;
use App\Models\User;


beforeEach(function () {
    $this->dm = User::factory()->dm()->create();
    $this->giocatore = User::factory()->player()->create();
});

it('chiuso agli ospiti', function () {
    $this->get(route('profile.warnings'))->assertRedirect(route('login'));
});

it('a chi non ne ha mai avuti, lo dice', function () {
    $this->actingAs($this->giocatore)
        ->get(route('profile.warnings'))
        ->assertOk()
        ->assertSee('Non hai mai ricevuto un richiamo');
});
// Lo storico dei richiami resta disponibile anche dopo la loro chiusura.
it('elenca i richiami col motivo, la data e la durata', function () {
    $richiamo = app(IssueWarning::class)->handle(
        $this->giocatore, $this->dm, 'Ha venduto due volte lo stesso anello.',
    );
    app(LiftWarning::class)->handle($richiamo, $this->dm, 'Ha rigato dritto per un mese.');

    $this->actingAs($this->giocatore)
        ->get(route('profile.warnings'))
        ->assertOk()
        ->assertSee('Ha venduto due volte lo stesso anello.')
        ->assertSee('Ha rigato dritto per un mese.')
        ->assertSee('Tolto il')
        ->assertDontSee('In corso');
});

it('e mette in cima quello attivo, con cosa comporta e il link alle azioni in attesa', function () {
    app(IssueWarning::class)->handle($this->giocatore, $this->dm, 'Prezzi gonfiati.');

    $this->actingAs($this->giocatore)
        ->get(route('profile.warnings'))
        ->assertOk()
        ->assertSee('Sei sotto richiamo')
        ->assertSee('In corso')
        ->assertSee(route('market.supervision'), false);
});

// L'interfaccia non espone al giocatore chi ha emesso o revocato il richiamo.
it('ma non dice chi l\'ha dato', function () {
    $dm = User::factory()->dm()->create(['name' => 'Il Severo']);
    app(IssueWarning::class)->handle($this->giocatore, $dm, 'Un motivo.');

    $this->actingAs($this->giocatore)
        ->get(route('profile.warnings'))
        ->assertOk()
        ->assertDontSee('Il Severo');
});

it('e non mostra i richiami di un altro', function () {
    $altro = User::factory()->player()->create();
    app(IssueWarning::class)->handle($altro, $this->dm, 'Roba sua e privata.');

    $this->actingAs($this->giocatore)
        ->get(route('profile.warnings'))
        ->assertOk()
        ->assertDontSee('Roba sua e privata.')
        ->assertSee('Non hai mai ricevuto un richiamo');
});

it('e il profilo ci manda solo chi ne ha avuti', function () {
    $this->actingAs($this->giocatore)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee(route('profile.warnings'), false);

    app(IssueWarning::class)->handle($this->giocatore, $this->dm, 'Un motivo.');

    $this->actingAs($this->giocatore)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(route('profile.warnings'), false);
});
