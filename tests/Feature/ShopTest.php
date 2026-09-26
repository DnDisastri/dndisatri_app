<?php

declare(strict_types=1);

use App\Actions\Market\BuyFromShop;
use App\Actions\Market\GrantGold;
use App\Enums\EquipmentSlot;
use App\Enums\LedgerAction;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\LedgerEntry;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

describe('acquisto dal negozio', function () {
    it('scala l\'oro, decrementa le scorte e consegna l\'oggetto', function () {
        $character = Character::factory()->create(['gp' => 200]);
        $item = MarketItem::factory()->named('Pozione di Cura', 50)->stock(3)->create();

        app(BuyFromShop::class)->handle($character, $item);

        expect($character->fresh()->gp)->toBe(150)
            ->and($item->fresh()->stock)->toBe(2)
            ->and($character->fresh()->ownsItem('Pozione di Cura'))->toBeTrue();
    });

    it('moltiplica il prezzo per la quantità', function () {
        $character = Character::factory()->create(['gp' => 200]);
        $item = MarketItem::factory()->named('Corda', 20)->stock(10)->create();

        app(BuyFromShop::class)->handle($character, $item, qty: 3);

        expect($character->fresh()->gp)->toBe(140)
            ->and($item->fresh()->stock)->toBe(7)
            ->and($character->fresh()->items()->where('name', 'Corda')->value('qty'))->toBe(3);
    });

    it('rifiuta l\'acquisto se l\'oro non basta', function () {
        $character = Character::factory()->create(['gp' => 30]);
        $item = MarketItem::factory()->named('Spadone', 50)->stock(5)->create();

        expect(fn () => app(BuyFromShop::class)->handle($character, $item))
            ->toThrow(MarketException::class);

        expect($character->fresh()->gp)->toBe(30)
            ->and($item->fresh()->stock)->toBe(5)
            ->and($character->fresh()->items()->count())->toBe(0);
    });

    it('rifiuta l\'acquisto se l\'articolo è esaurito', function () {
        $character = Character::factory()->create(['gp' => 500]);
        $item = MarketItem::factory()->soldOut()->create();

        expect(fn () => app(BuyFromShop::class)->handle($character, $item))
            ->toThrow(MarketException::class);

        expect($character->fresh()->gp)->toBe(500);
    });
// `stock: null` rappresenta disponibilità illimitata e non viene decrementato dagli acquisti.
    it('non esaurisce mai gli articoli a scorte infinite', function () {
        $character = Character::factory()->create(['gp' => 1000]);
        $item = MarketItem::factory()->named('Razione', 5)->unlimited()->create();

        foreach (range(1, 10) as $ignored) {
            app(BuyFromShop::class)->handle($character->fresh(), $item);
        }

        expect($item->fresh()->stock)->toBe(0)
            ->and($item->fresh()->isAvailable(99))->toBeTrue()
            ->and($character->fresh()->gp)->toBe(950);
    });

    it('rifiuta quantità assurde', function () {
        $character = Character::factory()->create(['gp' => 100]);
        $item = MarketItem::factory()->create();

        expect(fn () => app(BuyFromShop::class)->handle($character, $item, qty: 0))
            ->toThrow(MarketException::class);
    });
});

describe('protezione dalla corsa', function () {
    // Le righe vengono bloccate durante l'acquisto per evitare race condition su scorte e saldo.
    it('blocca le righe di articolo e personaggio', function () {
        // SQLite ignora `FOR UPDATE`, quindi questa verifica viene eseguita solo su MySQL.
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Il blocco di riga esiste solo su MySQL; i test girano su SQLite.');
        }

        $character = Character::factory()->create(['gp' => 500]);
        $item = MarketItem::factory()->stock(1)->create();

        DB::enableQueryLog();
        app(BuyFromShop::class)->handle($character, $item);
        $queries = collect(DB::getQueryLog())->pluck('query');

        expect($queries->filter(fn ($q) => str_contains($q, 'for update')))
            ->toHaveCount(2);
    });

    it('non lascia stato parziale quando qualcosa va storto', function () {
        $character = Character::factory()->create(['gp' => 10]);
        $item = MarketItem::factory()->named('Spadone', 50)->stock(2)->create();

        try {
            app(BuyFromShop::class)->handle($character, $item);
        } catch (MarketException) {
        }

        expect($character->fresh()->gp)->toBe(10)
            ->and($item->fresh()->stock)->toBe(2)
            ->and($character->fresh()->items()->count())->toBe(0)
            ->and(LedgerEntry::forCharacter($character)->count())->toBe(0);
    });
});

describe('inventario', function () {
    it('accorpa gli acquisti ripetuti invece di riempire di righe', function () {
        $character = Character::factory()->create(['gp' => 1000]);
        $item = MarketItem::factory()->named('Pozione di Cura', 50)->stock(10)->create();

        app(BuyFromShop::class)->handle($character, $item, qty: 2);
        app(BuyFromShop::class)->handle($character->fresh(), $item, qty: 3);

        expect($character->fresh()->items()->where('name', 'Pozione di Cura')->count())->toBe(1)
            ->and($character->fresh()->items()->where('name', 'Pozione di Cura')->value('qty'))->toBe(5);
    });
// Gli acquisti non si accorpano a righe equipaggiate per non alterare lo stato dell'equipaggiamento.
    it('non accorpa su una riga equipaggiata', function () {
        $character = Character::factory()->create(['gp' => 1000]);
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();
        $item = MarketItem::factory()->named('Cotta di Maglia', 75)->stock(5)->create();

        app(BuyFromShop::class)->handle($character, $item);

        expect($character->fresh()->items()->where('name', 'Cotta di Maglia')->count())->toBe(2);
    });

    it('toglie dall\'inventario partendo da ciò che è riposto', function () {
        $character = Character::factory()->create();
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();
        $character->addToInventory('Cotta di Maglia', 2);

        $removed = $character->removeFromInventory('Cotta di Maglia', 2);

        expect($removed)->toBe(2)
            ->and($character->fresh()->equipped(EquipmentSlot::Armor))->not->toBeNull();
    });
});

describe('il Registro', function () {
    it('scrive una riga a ogni acquisto, col saldo risultante', function () {
        $character = Character::factory()->create(['gp' => 200]);
        $item = MarketItem::factory()->named('Pozione di Cura', 50)->stock(5)->create();

        app(BuyFromShop::class)->handle($character, $item);

        $entry = LedgerEntry::forCharacter($character)->latestFirst()->first();

        expect($entry->action)->toBe(LedgerAction::Buy)
            ->and($entry->gp_delta)->toBe(-50)
            ->and($entry->gp_after)->toBe(150)
            ->and($entry->message)->toContain('Pozione di Cura');
    });

    it('registra chi ha causato il movimento', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['gp' => 100]);

        app(GrantGold::class)->handle($character, 250, $dm, 'Bottino di sessione');

        $entry = LedgerEntry::forCharacter($character)->latestFirst()->first();

        expect($entry->actor_id)->toBe($dm->id)
            ->and($entry->action)->toBe(LedgerAction::DmGold)
            ->and($entry->gp_delta)->toBe(250)
            ->and($entry->gp_after)->toBe(350)
            ->and($entry->message)->toContain('Bottino di sessione');
    });
// Il saldo finale deve coincidere con la somma dei movimenti registrati.
    it('il saldo torna sempre con la somma dei movimenti', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['gp' => 0]);
        $item = MarketItem::factory()->named('Corda', 20)->stock(10)->create();

        app(GrantGold::class)->handle($character, 500, $dm);
        app(BuyFromShop::class)->handle($character->fresh(), $item, qty: 4);
        app(GrantGold::class)->handle($character->fresh(), -100, $dm, 'Multa della gilda');

        expect($character->fresh()->gp)
            ->toBe(LedgerEntry::forCharacter($character)->sum('gp_delta'));
    });
});

describe('oro assegnato dal DM', function () {
    it('non manda il personaggio in debito', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create(['gp' => 40]);

        app(GrantGold::class)->handle($character, -500, $dm);

        expect($character->fresh()->gp)->toBe(0)
            ->and(LedgerEntry::forCharacter($character)->latestFirst()->first()->gp_delta)->toBe(-40);
    });
});

describe('il catalogo', function () {
    it('lo gestiscono solo gli admin', function () {
        $item = MarketItem::factory()->create();

        expect(User::factory()->admin()->create()->can('update', $item))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('update', $item))->toBeFalse()
            ->and(User::factory()->player()->create()->can('update', $item))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('create', MarketItem::class))->toBeFalse();
    });

    it('ma il negozio lo guardano tutti', function () {
        $item = MarketItem::factory()->create();

        expect(User::factory()->player()->create()->can('view', $item))->toBeTrue();
    });

    it('elenca solo ciò che è davvero disponibile', function () {
        MarketItem::factory()->stock(3)->create();
        MarketItem::factory()->unlimited()->create();
        MarketItem::factory()->soldOut()->create();

        expect(MarketItem::available()->count())->toBe(2);
    });
});
