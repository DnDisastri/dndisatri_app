<?php

declare(strict_types=1);

use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Actions\Users\IssueWarning;
use App\Enums\TradeStatus;
use App\Livewire\Market\Listings;
use App\Livewire\Market\Shop;
use App\Livewire\Market\Trades;
use App\Models\Character;
use App\Models\MarketItem;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

function giocatoreCon(int $gp = 100): Character
{
    return Character::factory()->for(User::factory()->player())->create(['gp' => $gp]);
}

describe('le tre porte si aprono', function () {
    it('a chi ha un personaggio vivo', function (string $rotta) {
        $character = giocatoreCon();

        $this->actingAs($character->user)->get(route($rotta))->assertOk();
    })->with(['market.shop', 'market.listings', 'market.trades']);

    it('e l\'ingresso porta all\'emporio', function () {
        $this->actingAs(giocatoreCon()->user)
            ->get(route('market.index'))
            ->assertRedirect(route('market.shop'));
    });

    it('ma non a chi non ha fatto l\'accesso', function () {
        $this->get(route('market.shop'))->assertRedirect(route('login'));
    });

    it('a un admin si aprono, e dicono che gli serve un personaggio', function () {
        // Gli admin non ne hanno per scelta: governano l'economia, non ci
        // giocano. La pagina lo spiega invece di mostrarsi rotta.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('market.shop'))
            ->assertOk()
            ->assertSee('Serve un personaggio in salute');
    });
});

describe('comprare all\'emporio', function () {
    it('toglie l\'oro e mette l\'oggetto nello zaino', function () {
        $character = giocatoreCon(100);
        $item = MarketItem::factory()->create(['name' => 'Corda di Seta', 'price' => 10]);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('buy', $item->id);

        expect($character->fresh()->gp)->toBe(90)
            ->and($character->fresh()->ownsItem('Corda di Seta'))->toBeTrue();
    });

    it('e senza soldi lo dice, invece di far finta di niente', function () {
        $character = giocatoreCon(1);
        $item = MarketItem::factory()->create(['price' => 500]);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('buy', $item->id)
            ->assertHasErrors('mercato');

        expect($character->fresh()->gp)->toBe(1);
    });
});


describe('cercare nel mercato', function () {
    it('trova per nome, per categoria e per descrizione', function (string $parola) {
        $character = giocatoreCon();

        MarketItem::factory()->create([
            'name' => 'Pozione di Cura',
            'category' => 'Pozioni',
            'details' => 'Recupera 2d4+2 punti ferita.',
        ]);
        MarketItem::factory()->create([
            'name' => 'Spada Lunga',
            'category' => 'Armi',
            'details' => 'Danno 1d8.',
        ]);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->set('cerca', $parola)
            ->assertSee('Pozione di Cura')
            ->assertDontSee('Spada Lunga');
    })->with(['Pozione', 'Pozioni', 'ferita']);

    it('e quando non trova niente lo dice con la parola cercata', function () {
        MarketItem::factory()->create(['name' => 'Spada Lunga']);

        Livewire::actingAs(giocatoreCon()->user)
            ->test(Shop::class)
            ->set('cerca', 'balestra')
            ->assertSee('Niente che somigli a «balestra»');
    });

    it('anche fra gli annunci', function () {
        $venditore = giocatoreCon();
        $venditore->addToInventory('Spada Lunga', value: 15);
        app(CreateListing::class)->handle($venditore->fresh(), 'Spada Lunga', 1, 20);

        Livewire::actingAs(giocatoreCon()->user)
            ->test(Listings::class)
            ->set('cerca', 'spada')
            ->assertSee('Spada Lunga')
            ->set('cerca', 'balestra')
            ->assertDontSee('Spada Lunga');
    });
});


describe('il riquadro di dettaglio', function () {
    it('si apre su un articolo e si richiude', function () {
        $character = giocatoreCon();
        $item = MarketItem::factory()->create(['details' => 'Recupera 2d4+2 punti ferita.']);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('apri', $item->id)
            ->assertSet('aperto', $item->id)
            ->assertSee('Recupera 2d4+2 punti ferita')
            ->call('chiudi')
            ->assertSet('aperto', null);
    });

    it('comprato, si toglie di mezzo', function () {
        $character = giocatoreCon(100);
        $item = MarketItem::factory()->create(['price' => 10]);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('apri', $item->id)
            ->call('buy', $item->id)
            ->assertSet('aperto', null);
    });

    it('ma se l\'acquisto non riesce resta aperto', function () {
        $character = giocatoreCon(1);
        $item = MarketItem::factory()->create(['price' => 500]);

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('apri', $item->id)
            ->call('buy', $item->id)
            ->assertSet('aperto', $item->id)
            ->assertHasErrors('mercato');
    });

    it('la quantità fa il totale, e comprarne tre ne prende tre', function () {
        $character = giocatoreCon(100);
        $item = MarketItem::factory()->named('Torcia', 10)->create();

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('apri', $item->id)
            ->set('quanti', 3)
            ->assertSee('30 mo')
            ->call('buy', $item->id)
            ->assertHasNoErrors();

        expect($character->fresh()->gp)->toBe(70)
            ->and($character->fresh()->ownsItem('Torcia', 3))->toBeTrue();
    });
// Durante gli aggiornamenti Livewire la richiesta non usa la route originale, quindi lo stato attivo non può dipendere solo da `routeIs()`.
    it('e la porta aperta resta accesa anche dopo', function () {
        $item = MarketItem::factory()->create();

        Livewire::actingAs(giocatoreCon()->user)
            ->test(Shop::class)
            ->assertSeeHtml('aria-current="page"')
            ->call('apri', $item->id)
            ->assertSeeHtml('aria-current="page"');
    });

    it('e negli annunci si apre allo stesso modo', function () {
        $venditore = giocatoreCon();
        $venditore->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($venditore->fresh(), 'Spada Lunga', 1, 20);

        Livewire::actingAs(giocatoreCon(100)->user)
            ->test(Listings::class)
            ->call('apri', $listing->id)
            ->assertSet('aperto', $listing->id)
            ->assertSee('Compra')
            ->call('buy', $listing->id)
            ->assertSet('aperto', null);
    });
});


describe('la bacheca divide i miei dagli altri', function () {
    it('mette i propri in una sezione loro', function () {
        $io = giocatoreCon();
        $io->addToInventory('Spada Lunga', value: 15);
        app(CreateListing::class)->handle($io->fresh(), 'Spada Lunga', 1, 20);

        $altro = giocatoreCon();
        $altro->addToInventory('Scudo', value: 10);
        app(CreateListing::class)->handle($altro->fresh(), 'Scudo', 1, 15);

        $html = Livewire::actingAs($io->user)->test(Listings::class)
            ->assertSee('I miei oggetti')
            ->assertSee('In vendita dagli altri')
            ->html();

        // I propri stanno prima, e sotto il titolo giusto.
        expect(strpos($html, 'Spada Lunga'))->toBeLessThan(strpos($html, 'Scudo'));
    });

    it('e senza roba propria resta un elenco solo', function () {
        $altro = giocatoreCon();
        $altro->addToInventory('Scudo', value: 10);
        app(CreateListing::class)->handle($altro->fresh(), 'Scudo', 1, 15);

        Livewire::actingAs(giocatoreCon()->user)->test(Listings::class)
            ->assertSee('In vendita')
            ->assertDontSee('I miei oggetti')
            ->assertSee('Scudo');
    });
});

describe('gli annunci', function () {
    it('si pubblicano, e l\'oggetto esce dallo zaino', function () {
        $character = giocatoreCon();
        $character->addToInventory('Spada Lunga', value: 15);

        Livewire::actingAs($character->user)
            ->test(Listings::class)
            ->set('itemName', 'Spada Lunga')
            ->set('sellQty', 1)
            ->set('price', 20)
            ->call('sell')
            ->assertHasNoErrors();

        expect($character->fresh()->ownsItem('Spada Lunga'))->toBeFalse();
    });

    it('si comprano, e la roba cambia mani', function () {
        $venditore = giocatoreCon();
        $venditore->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($venditore->fresh(), 'Spada Lunga', 1, 20);

        $compratore = giocatoreCon(100);

        Livewire::actingAs($compratore->user)
            ->test(Listings::class)
            ->call('buy', $listing->id)
            ->assertHasNoErrors();

        expect($compratore->fresh()->gp)->toBe(80)
            ->and($compratore->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($venditore->fresh()->gp)->toBe(120);
    });

    it('e il proprio si ritira, con la roba che torna indietro', function () {
        $character = giocatoreCon();
        $character->addToInventory('Spada Lunga', value: 15);
        $listing = app(CreateListing::class)->handle($character->fresh(), 'Spada Lunga', 1, 20);

        Livewire::actingAs($character->user)
            ->test(Listings::class)
            ->call('withdraw', $listing->id);

        expect($character->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });
});

describe('gli scambi', function () {
    it('si propongono, e l\'oro non si muove finché l\'altro non accetta', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon();

        Livewire::actingAs($from->user)
            ->test(Trades::class)
            ->set('toCharacterId', $to->id)
            ->set('giveGp', 10)
            ->call('propose')
            ->assertHasNoErrors();

        expect(Trade::awaiting($to)->count())->toBe(1)
            ->and($from->fresh()->gp)->toBe(100);
    });

    it('senza destinatario la proposta non parte', function () {
        $from = giocatoreCon(100);

        Livewire::actingAs($from->user)
            ->test(Trades::class)
            ->set('giveGp', 10)
            ->call('propose')
            ->assertHasErrors('scambio');
    });


    it('arrivando dalla vetrina di un altro, lo trova già scelto', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon();

        Livewire::withQueryParams(['a' => $to->id])
            ->actingAs($from->user)
            ->test(Trades::class)
            ->assertSet('toCharacterId', $to->id);
    });
// Il destinatario precompilato arriva dalla query string e viene accettato solo se identifica un personaggio valido e disponibile.
    it('ma un id inventato lo lascia vuoto', function () {
        $from = giocatoreCon(100);
        $morto = Character::factory()->fallen()->create();

        foreach ([99999, $morto->id] as $a) {
            Livewire::withQueryParams(['a' => $a])
                ->actingAs($from->user)
                ->test(Trades::class)
                ->assertSet('toCharacterId', null);
        }
    });

    it('e non pre-sceglie il personaggio con cui sto usando il mercato', function () {
        $from = giocatoreCon(100);

        Livewire::withQueryParams(['a' => $from->id])
            ->actingAs($from->user)
            ->test(Trades::class)
            ->assertSet('toCharacterId', null);
    });

    it('arrivano a chi li riceve e si accettano', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon(0);

        $trade = app(CreateTrade::class)->handle(from: $from, to: $to, giveGp: 30);

        Livewire::actingAs($to->user)
            ->test(Trades::class)
            ->call('accept', $trade->id)
            ->assertHasNoErrors();

        expect($trade->fresh()->status)->toBe(TradeStatus::Accepted)
            ->and($to->fresh()->gp)->toBe(30)
            ->and($from->fresh()->gp)->toBe(70);
    });

    it('e si rifiutano senza muovere niente', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon(0);

        $trade = app(CreateTrade::class)->handle(from: $from, to: $to, giveGp: 30);

        Livewire::actingAs($to->user)
            ->test(Trades::class)
            ->call('reject', $trade->id);

        expect($trade->fresh()->status)->toBe(TradeStatus::Rejected)
            ->and($from->fresh()->gp)->toBe(100);
    });

    it('avvisa sulla card quando non è più eseguibile', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon(100);
        $from->addToInventory('Spada Lunga');

        $trade = app(CreateTrade::class)->handle(
            from: $from, to: $to, give: [['name' => 'Spada Lunga']],
        );

        // Chi ha proposto la vende prima che l'altro risponda.
        $from->removeFromInventory('Spada Lunga');

        Livewire::actingAs($to->user)
            ->test(Trades::class)
            ->assertSee('Non si può più fare')
            ->assertSee($from->name.' non ha più 1× Spada Lunga');
    });

    it('e se tutto torna, non avvisa niente', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon(100);

        $trade = app(CreateTrade::class)->handle(from: $from, to: $to, giveGp: 30);

        Livewire::actingAs($to->user)
            ->test(Trades::class)
            ->assertDontSee('Non si può più fare');
    });
});
// Le pagine del mercato devono passare dal Supervisor, altrimenti un utente sotto richiamo potrebbe aggirare la vigilanza.
describe('sotto richiamo, le pagine non scavalcano la vigilanza', function () {
    it('pubblicare un annuncio finisce in attesa invece che in bacheca', function () {
        $character = giocatoreCon();
        $character->addToInventory('Spada Lunga', value: 15);

        app(IssueWarning::class)->handle(
            $character->user, User::factory()->dm()->create(), 'Prova',
        );

        Livewire::actingAs($character->user)
            ->test(Listings::class)
            ->set('itemName', 'Spada Lunga')
            ->set('sellQty', 1)
            ->set('price', 20)
            ->call('sell');

        expect(SupervisedAction::pending()->count())->toBe(1)
            // L'oggetto non si è mosso: niente succede finché un DM non decide.
            ->and($character->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });

    it('e proporre uno scambio pure', function () {
        $from = giocatoreCon(100);
        $to = giocatoreCon();

        app(IssueWarning::class)->handle(
            $from->user, User::factory()->dm()->create(), 'Prova',
        );

        Livewire::actingAs($from->user)
            ->test(Trades::class)
            ->set('toCharacterId', $to->id)
            ->set('giveGp', 10)
            ->call('propose');

        expect(SupervisedAction::pending()->count())->toBe(1)
            ->and($from->fresh()->gp)->toBe(100);
    });

    it('comprare all\'emporio invece passa: non c\'è nessuno da truffare', function () {
        $character = giocatoreCon(100);
        $item = MarketItem::factory()->create(['price' => 10]);

        app(IssueWarning::class)->handle(
            $character->user, User::factory()->dm()->create(), 'Prova',
        );

        Livewire::actingAs($character->user)
            ->test(Shop::class)
            ->call('buy', $item->id);

        expect($character->fresh()->gp)->toBe(90)
            ->and(SupervisedAction::count())->toBe(0);
    });
});
