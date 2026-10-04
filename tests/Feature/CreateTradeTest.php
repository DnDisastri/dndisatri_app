<?php

declare(strict_types=1);

use App\Actions\Market\AcceptTrade;
use App\Actions\Market\CreateTrade;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\Trade;

beforeEach(function () {
    $this->anna = Character::factory()->create(['name' => 'Anna', 'gp' => 100]);
    $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);
});

describe('la proposta', function () {
    it('registra oggetti e oro nelle due direzioni', function () {
        $this->anna->addToInventory('Spada Lunga', valueCp: 1500);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna,
            to: $this->bruno,
            give: [['name' => 'Spada Lunga']],
            want: [['name' => 'Scudo']],
            giveCp: 1000,
            message: 'Ti serve più di quanto serva a me.',
        );

        expect($trade->givenItems()->pluck('name')->all())->toBe(['Spada Lunga'])
            ->and($trade->wantedItems()->pluck('name')->all())->toBe(['Scudo'])
            ->and($trade->give_cp)->toBe(1000)
            ->and($trade->isOpen())->toBeTrue();
    });

    it('non muove niente dagli inventari', function () {
        $this->anna->addToInventory('Spada Lunga');

        app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );

        expect($this->anna->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($this->anna->fresh()->gp)->toBe(100);
    });

    it('copia i dettagli dell\'oggetto offerto', function () {
        $this->anna->addToInventory('Spada Lunga', category: 'Armi', valueCp: 1500, details: 'Intaccata');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );

        $offered = $trade->givenItems()->first();

        expect($offered->value_cp)->toBe(1500)
            ->and($offered->category)->toBe('Armi')
            ->and($offered->details)->toBe('Intaccata');
    });

    it('una proposta di solo oro è valida', function () {
        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, giveCp: 5000, wantCp: 0,
        );

        expect($trade->give_cp)->toBe(5000);
    });
});

describe('cosa non si può proporre', function () {
    it('uno scambio con sé stessi', function () {
        expect(fn () => app(CreateTrade::class)->handle(from: $this->anna, to: $this->anna, giveCp: 1000))
            ->toThrow(MarketException::class, 'a te stesso');
    });

    it('uno scambio vuoto', function () {
        expect(fn () => app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno))
            ->toThrow(MarketException::class, 'vuoto');
    });

    it('un oggetto che non si ha', function () {
        expect(fn () => app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        ))->toThrow(MarketException::class);
    });

    it('più pezzi di quanti se ne hanno', function () {
        $this->anna->addToInventory('Pugnale', qty: 2);

        expect(fn () => app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Pugnale', 'qty' => 5]],
        ))->toThrow(MarketException::class);
    });

    it('più oro di quanto se ne ha', function () {
        expect(fn () => app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, giveCp: 50000,
        ))->toThrow(MarketException::class);
    });

    it('con un personaggio caduto', function () {
        $morto = Character::factory()->fallen()->create();

        expect(fn () => app(CreateTrade::class)->handle(
            from: $this->anna, to: $morto, giveCp: 1000,
        ))->toThrow(MarketException::class, 'caduto');
    });
    // Gli oggetti richiesti vengono validati all'accettazione, perché il destinatario può procurarseli dopo la proposta.
    it('quello che si chiede invece non viene controllato', function () {

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, want: [['name' => 'Scudo']],
        );

        expect($trade->wantedItems())->toHaveCount(1);
    });
});

describe('il giro completo', function () {
    it('proposta e accettazione si scambiano davvero le cose', function () {
        $this->anna->addToInventory('Spada Lunga', valueCp: 1500);
        $this->bruno->addToInventory('Scudo', valueCp: 1000);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna,
            to: $this->bruno,
            give: [['name' => 'Spada Lunga']],
            want: [['name' => 'Scudo']],
            giveCp: 2000,
        );

        app(AcceptTrade::class)->handle($trade);

        expect($this->anna->fresh()->ownsItem('Scudo'))->toBeTrue()
            ->and($this->bruno->fresh()->ownsItem('Spada Lunga'))->toBeTrue()
            ->and($this->anna->fresh()->gp)->toBe(80)
            ->and($this->bruno->fresh()->gp)->toBe(120);
    });

    it('e una proposta diventata impossibile fallisce all\'accettazione', function () {
        $this->anna->addToInventory('Spada Lunga');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );

        $this->anna->removeFromInventory('Spada Lunga');

        expect(fn () => app(AcceptTrade::class)->handle($trade->fresh()))
            ->toThrow(MarketException::class, 'non è più valido');
    });
});

// `deliveryProblems()` replica la verifica di consegna in sola lettura per poter avvisare prima dell'accettazione.
describe('se lo scambio non è più eseguibile', function () {
    function conRelazioni(Trade $trade): Trade
    {
        return Trade::with(['from', 'to', 'items'])->findOrFail($trade->getKey());
    }

    it('appena fatta, si può accettare', function () {
        $this->anna->addToInventory('Spada Lunga');
        $this->bruno->addToInventory('Scudo');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno,
            give: [['name' => 'Spada Lunga']], want: [['name' => 'Scudo']], giveCp: 2000,
        );

        expect(conRelazioni($trade)->deliveryProblems())->toBe([])
            ->and(conRelazioni($trade)->canBeAccepted())->toBeTrue();
    });

    it('se chi ha proposto ha venduto l\'oggetto, lo nomina e non si accetta', function () {
        $this->anna->addToInventory('Spada Lunga');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );

        $this->anna->removeFromInventory('Spada Lunga');

        expect(conRelazioni($trade)->deliveryProblems())->toContain('Anna non ha più 1× Spada Lunga')
            ->and(conRelazioni($trade)->canBeAccepted())->toBeFalse();
    });

    it('se a chi riceve non bastano le monete, lo dice col conto', function () {
        $povero = Character::factory()->create(['name' => 'Ciro', 'gp' => 5, 'sp' => 3]);
        $this->anna->addToInventory('Spada Lunga');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $povero,
            give: [['name' => 'Spada Lunga']], wantCp: 3000,
        );

        expect(conRelazioni($trade)->deliveryProblems())->toContain('Ciro non ha abbastanza monete (5 mo 3 ma su 30 mo)');
    });

    it('e guarda tutte e due le parti', function () {
        $poveraccio = Character::factory()->create(['name' => 'Dario', 'gp' => 0]);

        $this->anna->addToInventory('Spada Lunga');
        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $poveraccio,
            give: [['name' => 'Spada Lunga']], wantCp: 5000,
        );
        $this->anna->removeFromInventory('Spada Lunga');

        expect(conRelazioni($trade)->deliveryProblems())
            ->toContain('Anna non ha più 1× Spada Lunga')
            ->toContain('Dario non ha abbastanza monete (0 mo su 50 mo)');
    });
});
