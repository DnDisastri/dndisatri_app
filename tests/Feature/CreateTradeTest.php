<?php

declare(strict_types=1);

use App\Actions\Market\AcceptTrade;
use App\Actions\Market\CreateTrade;
use App\Exceptions\MarketException;
use App\Models\Character;


beforeEach(function () {
    $this->anna = Character::factory()->create(['name' => 'Anna', 'gp' => 100]);
    $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);
});

describe('la proposta', function () {
    it('registra oggetti e oro nelle due direzioni', function () {
        $this->anna->addToInventory('Spada Lunga', value: 15);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna,
            to: $this->bruno,
            give: [['name' => 'Spada Lunga']],
            want: [['name' => 'Scudo']],
            giveGp: 10,
            message: 'Ti serve più di quanto serva a me.',
        );

        expect($trade->givenItems()->pluck('name')->all())->toBe(['Spada Lunga'])
            ->and($trade->wantedItems()->pluck('name')->all())->toBe(['Scudo'])
            ->and($trade->give_gp)->toBe(10)
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
        $this->anna->addToInventory('Spada Lunga', category: 'Armi', value: 15, details: 'Intaccata');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, give: [['name' => 'Spada Lunga']],
        );

        $offered = $trade->givenItems()->first();

        expect($offered->value)->toBe(15)
            ->and($offered->category)->toBe('Armi')
            ->and($offered->details)->toBe('Intaccata');
    });

    it('una proposta di solo oro è valida', function () {
        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno, giveGp: 50, wantGp: 0,
        );

        expect($trade->give_gp)->toBe(50);
    });
});

describe('cosa non si può proporre', function () {
    it('uno scambio con sé stessi', function () {
        expect(fn () => app(CreateTrade::class)->handle(from: $this->anna, to: $this->anna, giveGp: 10))
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
            from: $this->anna, to: $this->bruno, giveGp: 500,
        ))->toThrow(MarketException::class);
    });

    it('con un personaggio caduto', function () {
        $morto = Character::factory()->fallen()->create();

        expect(fn () => app(CreateTrade::class)->handle(
            from: $this->anna, to: $morto, giveGp: 10,
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
        $this->anna->addToInventory('Spada Lunga', value: 15);
        $this->bruno->addToInventory('Scudo', value: 10);

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna,
            to: $this->bruno,
            give: [['name' => 'Spada Lunga']],
            want: [['name' => 'Scudo']],
            giveGp: 20,
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
    function conRelazioni(App\Models\Trade $trade): App\Models\Trade
    {
        return App\Models\Trade::with(['from', 'to', 'items'])->findOrFail($trade->getKey());
    }

    it('appena fatta, si può accettare', function () {
        $this->anna->addToInventory('Spada Lunga');
        $this->bruno->addToInventory('Scudo');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $this->bruno,
            give: [['name' => 'Spada Lunga']], want: [['name' => 'Scudo']], giveGp: 20,
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

    it('se a chi riceve non basta l\'oro, lo dice col conto', function () {
        $povero = Character::factory()->create(['name' => 'Ciro', 'gp' => 5]);
        $this->anna->addToInventory('Spada Lunga');

        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $povero,
            give: [['name' => 'Spada Lunga']], wantGp: 30,
        );

        expect(conRelazioni($trade)->deliveryProblems())->toContain('Ciro non ha abbastanza oro (5/30 mo)');
    });

    it('e guarda tutte e due le parti', function () {
        $poveraccio = Character::factory()->create(['name' => 'Dario', 'gp' => 0]);

        $this->anna->addToInventory('Spada Lunga');
        $trade = app(CreateTrade::class)->handle(
            from: $this->anna, to: $poveraccio,
            give: [['name' => 'Spada Lunga']], wantGp: 50,
        );
        $this->anna->removeFromInventory('Spada Lunga');

        expect(conRelazioni($trade)->deliveryProblems())
            ->toContain('Anna non ha più 1× Spada Lunga')
            ->toContain('Dario non ha abbastanza oro (0/50 mo)');
    });
});
