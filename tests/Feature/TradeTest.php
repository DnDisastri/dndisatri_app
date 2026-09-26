<?php

declare(strict_types=1);

use App\Actions\Market\AcceptTrade;
use App\Actions\Market\ResolveTrade;
use App\Enums\LedgerAction;
use App\Enums\TradeStatus;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\LedgerEntry;
use App\Models\Trade;
use App\Models\User;

describe('scambio accettato', function () {
    it('sposta oggetti e oro nelle due direzioni', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 50]);
        $anna->addToInventory('Spada Lunga', 1, 'Armi', 15);
        $bruno->addToInventory('Scudo', 1, 'Armature', 10);

        $trade = Trade::factory()->between($anna, $bruno)
            ->gold(give: 20, want: 0)
            ->giving('Spada Lunga')
            ->wanting('Scudo')
            ->create();

        app(AcceptTrade::class)->handle($trade);

        expect($anna->fresh()->ownsItem('Scudo'))->toBeTrue()
            ->and($anna->fresh()->ownsItem('Spada Lunga'))->toBeFalse()
            ->and($bruno->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($bruno->fresh()->ownsItem('Scudo'))->toBeFalse()
            ->and($anna->fresh()->gp)->toBe(80)
            ->and($bruno->fresh()->gp)->toBe(70)
            ->and($trade->fresh()->status)->toBe(TradeStatus::Accepted);
    });

    it('regge lo scambio di solo oro', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 100]);

        $trade = Trade::factory()->between($anna, $bruno)->gold(give: 30, want: 10)->create();

        app(AcceptTrade::class)->handle($trade);

        expect($anna->fresh()->gp)->toBe(80)
            ->and($bruno->fresh()->gp)->toBe(120);
    });

    it('scrive nel Registro il saldo netto per ciascuno', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 100]);

        $trade = Trade::factory()->between($anna, $bruno)->gold(give: 30, want: 10)->create();
        app(AcceptTrade::class)->handle($trade);

        $annaEntry = LedgerEntry::forCharacter($anna)->latestFirst()->first();
        $brunoEntry = LedgerEntry::forCharacter($bruno)->latestFirst()->first();

        expect($annaEntry->action)->toBe(LedgerAction::Trade)
            ->and($annaEntry->gp_delta)->toBe(-20)
            ->and($brunoEntry->gp_delta)->toBe(20);
    });
});
// Nelle proposte di scambio i beni restano negli inventari fino all'accettazione e vengono rivalidati in quel momento.
describe('la verifica riguarda entrambe le parti', function () {
    it('fallisce se chi ha proposto non ha più l\'oggetto', function () {

        $anna = Character::factory()->create();
        $bruno = Character::factory()->create();
        $anna->addToInventory('Spada Lunga', 1);

        $trade = Trade::factory()->between($anna, $bruno)->giving('Spada Lunga')->create();

        $anna->removeFromInventory('Spada Lunga', 1);

        expect(fn () => app(AcceptTrade::class)->handle($trade))
            ->toThrow(MarketException::class);

        expect($trade->fresh()->isOpen())->toBeTrue();
    });

    it('fallisce se il destinatario non ha ciò che gli è stato chiesto', function () {
        $anna = Character::factory()->create();
        $bruno = Character::factory()->create();

        $trade = Trade::factory()->between($anna, $bruno)->wanting('Scudo')->create();

        expect(fn () => app(AcceptTrade::class)->handle($trade))
            ->toThrow(MarketException::class);
    });

    it('fallisce se manca l\'oro a una delle due parti', function () {
        $anna = Character::factory()->create(['gp' => 5]);
        $bruno = Character::factory()->create(['gp' => 500]);

        $trade = Trade::factory()->between($anna, $bruno)->gold(give: 100)->create();

        expect(fn () => app(AcceptTrade::class)->handle($trade))
            ->toThrow(MarketException::class);

        expect($anna->fresh()->gp)->toBe(5)
            ->and($bruno->fresh()->gp)->toBe(500);
    });
// La consegna è atomica: se una delle due parti non può adempiere, nessun bene o oro si muove.
    it('non lascia mai uno scambio a metà', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 0]);
        $anna->addToInventory('Spada Lunga', 1);

        $trade = Trade::factory()->between($anna, $bruno)
            ->giving('Spada Lunga')
            ->gold(want: 50)
            ->create();

        expect(fn () => app(AcceptTrade::class)->handle($trade))
            ->toThrow(MarketException::class);

        expect($anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($anna->fresh()->gp)->toBe(100)
            ->and($bruno->fresh()->items()->count())->toBe(0)
            ->and(LedgerEntry::count())->toBe(0);
    });
});

describe('proposte chiuse senza eseguirle', function () {
    it('rifiutare non muove nulla', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 100]);
        $anna->addToInventory('Spada Lunga', 1);

        $trade = Trade::factory()->between($anna, $bruno)->giving('Spada Lunga')->gold(give: 50)->create();

        app(ResolveTrade::class)->handle($trade, TradeStatus::Rejected);

        expect($trade->fresh()->status)->toBe(TradeStatus::Rejected)
            ->and($anna->fresh()->gp)->toBe(100)
            ->and($anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });

    it('una proposta chiusa non si accetta più', function () {
        $trade = Trade::factory()->create();
        app(ResolveTrade::class)->handle($trade, TradeStatus::Cancelled);

        expect(fn () => app(AcceptTrade::class)->handle($trade->fresh()))
            ->toThrow(MarketException::class);
    });

    it('non si accetta da qui: serve l\'azione giusta', function () {
        $trade = Trade::factory()->create();

        expect(fn () => app(ResolveTrade::class)->handle($trade, TradeStatus::Accepted))
            ->toThrow(InvalidArgumentException::class);
    });

    it('uno scambio già accettato non si accetta due volte', function () {
        $anna = Character::factory()->create(['gp' => 100]);
        $bruno = Character::factory()->create(['gp' => 100]);
        $trade = Trade::factory()->between($anna, $bruno)->gold(give: 10)->create();

        app(AcceptTrade::class)->handle($trade);

        expect(fn () => app(AcceptTrade::class)->handle($trade->fresh()))
            ->toThrow(MarketException::class);

        expect($anna->fresh()->gp)->toBe(90);
    });
});

describe('permessi', function () {
    it('accetta e rifiuta solo il destinatario', function () {
        $proposer = User::factory()->player()->create();
        $recipient = User::factory()->player()->create();
        $stranger = User::factory()->player()->create();

        $trade = Trade::factory()->between(
            Character::factory()->ownedBy($proposer)->create(),
            Character::factory()->ownedBy($recipient)->create(),
        )->create();

        expect($recipient->can('accept', $trade))->toBeTrue()
            ->and($recipient->can('reject', $trade))->toBeTrue()
            ->and($proposer->can('accept', $trade))->toBeFalse()
            ->and($stranger->can('accept', $trade))->toBeFalse();
    });

    it('ritira la proposta solo chi l\'ha fatta', function () {
        $proposer = User::factory()->player()->create();
        $recipient = User::factory()->player()->create();

        $trade = Trade::factory()->between(
            Character::factory()->ownedBy($proposer)->create(),
            Character::factory()->ownedBy($recipient)->create(),
        )->create();

        expect($proposer->can('cancel', $trade))->toBeTrue()
            ->and($recipient->can('cancel', $trade))->toBeFalse();
    });

    it('lo scambio lo vedono le due parti, i DM e gli admin', function () {
        $proposer = User::factory()->player()->create();
        $recipient = User::factory()->player()->create();

        $trade = Trade::factory()->between(
            Character::factory()->ownedBy($proposer)->create(),
            Character::factory()->ownedBy($recipient)->create(),
        )->create();

        expect($proposer->can('view', $trade))->toBeTrue()
            ->and($recipient->can('view', $trade))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('view', $trade))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('view', $trade))->toBeTrue()
            ->and(User::factory()->player()->create()->can('view', $trade))->toBeFalse();
    });
});
