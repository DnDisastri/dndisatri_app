<?php

declare(strict_types=1);

use App\Actions\Market\GrantGold;
use App\Enums\LedgerAction;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create(['name' => 'Grimm', 'gp' => 100]);
});

it('elenca i movimenti con la variazione e il saldo', function () {
    app(GrantGold::class)->handle($this->pg, 50, User::factory()->dm()->create(), 'Bottino della serata');

    $this->actingAs($this->giocatore)
        ->get(route('characters.ledger', $this->pg))
        ->assertOk()
        ->assertSee('Bottino della serata')
        ->assertSee('Oro dal DM')
        ->assertSee('+50 mo')
        ->assertSee('saldo dopo: 150 mo');
});

it('mette il movimento più recente per primo', function () {
    $dm = User::factory()->dm()->create();
    app(GrantGold::class)->handle($this->pg, 10, $dm, 'Il primo');
    app(GrantGold::class)->handle($this->pg, 10, $dm, 'Il secondo');

    $html = $this->actingAs($this->giocatore)
        ->get(route('characters.ledger', $this->pg))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'Il secondo'))->toBeLessThan(strpos($html, 'Il primo'));
});

it('spiega la pagina vuota invece di mostrarla vuota', function () {
    $this->actingAs($this->giocatore)
        ->get(route('characters.ledger', $this->pg))
        ->assertOk()
        ->assertSee('Nessun movimento');
});

// Il registro è append-only: un annullamento resta visibile invece di cancellare il movimento originale.
it('segna i movimenti annullati senza toglierli', function () {
    app(GrantGold::class)->handle($this->pg, 50, User::factory()->dm()->create(), 'Oro di troppo');

    LedgerEntry::forCharacter($this->pg)->latestFirst()->first()
        ->forceFill(['reversed_at' => now()])->save();

    $this->actingAs($this->giocatore)
        ->get(route('characters.ledger', $this->pg))
        ->assertOk()
        ->assertSee('Oro di troppo')
        ->assertSee('Annullato il');
});
// Il registro del singolo personaggio contiene dati privati e resta visibile solo al proprietario e a chi conduce.
describe('chi lo può leggere', function () {

    it('lo nega a un altro giocatore', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('characters.ledger', $this->pg))
            ->assertForbidden();
    });

    it('lo apre a chi conduce', function (string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get(route('characters.ledger', $this->pg))
            ->assertOk();
    })->with(['dm', 'admin']);
});


it('si raggiunge dai miei eroi', function () {
    $this->actingAs($this->giocatore)
        ->get(route('characters.index'))
        ->assertOk()
        ->assertSee(route('characters.ledger', $this->pg));
});


describe('il registro di tutti (M20)', function () {
    it('dà a chi conduce la barra dei filtri, e al giocatore no', function () {

        $this->actingAs($this->giocatore)
            ->get(route('characters.ledger', $this->pg))
            ->assertOk()
            ->assertDontSee('Tutti i personaggi');

        $this->actingAs(User::factory()->dm()->create())
            ->get(route('characters.ledger', $this->pg))
            ->assertOk()
            ->assertSee('Tutti i personaggi')
            ->assertSee('Tutti i tipi')
            ->assertSee('Da sempre');
    });

    it('su «tutti» mostra i movimenti di ogni personaggio, con il nome di chi', function () {
        $dm = User::factory()->dm()->create();
        $vex = Character::factory()->create(['name' => 'Vex']);
        app(GrantGold::class)->handle($this->pg, 50, $dm, 'Oro a Grimm');
        app(GrantGold::class)->handle($vex, 30, $dm, 'Oro a Vex');

        $this->actingAs($dm)
            ->get(route('characters.ledger', [$this->pg, 'pg' => 'tutti']))
            ->assertOk()
            ->assertSee('Oro a Grimm')
            ->assertSee('Oro a Vex')
            ->assertSee('Grimm')
            ->assertSee('Vex');
    });

    it('senza «tutti» resta a questo personaggio soltanto', function () {
        $dm = User::factory()->dm()->create();
        $vex = Character::factory()->create(['name' => 'Vex']);
        app(GrantGold::class)->handle($this->pg, 50, $dm, 'Oro a Grimm');
        app(GrantGold::class)->handle($vex, 30, $dm, 'Oro a Vex');

        $this->actingAs($dm)
            ->get(route('characters.ledger', $this->pg))
            ->assertOk()
            ->assertSee('Oro a Grimm')
            ->assertDontSee('Oro a Vex');
    });

    it('filtra per tipo di movimento', function () {
        $dm = User::factory()->dm()->create();
        app(GrantGold::class)->handle($this->pg, 50, $dm, 'Oro assegnato');
        $this->pg->ledgerEntries()->create([
            'action' => LedgerAction::Buy, 'gp_delta' => -20, 'gp_after' => 30,
            'message' => 'Comprata una corda',
        ]);

        $this->actingAs($dm)
            ->get(route('characters.ledger', [$this->pg, 'tipo' => LedgerAction::DmGold->value]))
            ->assertOk()
            ->assertSee('Oro assegnato')
            ->assertDontSee('Comprata una corda');
    });

    it('filtra per periodo, e lascia fuori il vecchio', function () {
        $dm = User::factory()->dm()->create();
        app(GrantGold::class)->handle($this->pg, 50, $dm, 'Bottino di ieri');

        $this->pg->ledgerEntries()->create([
            'action' => LedgerAction::DmGold, 'gp_delta' => 10, 'gp_after' => 110,
            'message' => 'Bottino di tre mesi fa',
        ])->forceFill(['created_at' => now()->subDays(100)])->save();

        $this->actingAs($dm)
            ->get(route('characters.ledger', [$this->pg, 'periodo' => 30]))
            ->assertOk()
            ->assertSee('Bottino di ieri')
            ->assertDontSee('Bottino di tre mesi fa');
    });
// I filtri non sono autorizzazione: forzare `?pg=tutti` non deve esporre i registri altrui.
    it('non si apre a un giocatore che forza «tutti» nell\'indirizzo', function () {
        $vex = Character::factory()->create(['name' => 'Vex']);
        app(GrantGold::class)->handle($vex, 30, User::factory()->dm()->create(), 'Oro a Vex');

        $this->actingAs($this->giocatore)
            ->get(route('characters.ledger', [$this->pg, 'pg' => 'tutti']))
            ->assertOk()
            ->assertDontSee('Oro a Vex');
    });
});
