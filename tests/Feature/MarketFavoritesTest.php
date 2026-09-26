<?php

declare(strict_types=1);

use App\Livewire\Market\Favorites;
use App\Livewire\Market\Shop;
use App\Models\Character;
use App\Models\MarketItem;
use App\Models\User;
use Livewire\Livewire;

// I preferiti appartengono al personaggio, così due personaggi dello stesso giocatore mantengono liste indipendenti.
beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->eroe = Character::factory()->for($this->giocatore)->create(['name' => 'Grimm', 'gp' => 500]);
});

it('la stella mette l\'articolo fra i preferiti', function () {
    $pozione = MarketItem::factory()->named('Pozione di Cura')->create();

    Livewire::actingAs($this->giocatore)
        ->test(Shop::class)
        ->call('preferisci', $pozione->id);

    expect($this->eroe->favoriteItems()->pluck('name')->all())->toBe(['Pozione di Cura']);
});

it('e premuta di nuovo lo toglie', function () {
    $pozione = MarketItem::factory()->create();
    $this->eroe->favoriteItems()->attach($pozione);

    Livewire::actingAs($this->giocatore)
        ->test(Shop::class)
        ->call('preferisci', $pozione->id);

    expect($this->eroe->favoriteItems()->count())->toBe(0);
});


it('i preferiti stanno in cima all\'emporio', function () {
    MarketItem::factory()->named('Ampolla Sacra')->create();
    $zaino = MarketItem::factory()->named('Zaino di Cuoio')->create();

    $this->eroe->favoriteItems()->attach($zaino);

    $html = $this->actingAs($this->giocatore)->get(route('market.shop'))->assertOk()->getContent();

    expect(strpos($html, 'Zaino di Cuoio'))->toBeLessThan(strpos($html, 'Ampolla Sacra'));
});


it('sono del personaggio e non del giocatore', function () {
    $ladro = Character::factory()->for($this->giocatore)->create(['name' => 'Vex']);
    $grimaldelli = MarketItem::factory()->named('Arnesi da Scasso')->create();

    Livewire::actingAs($this->giocatore)
        ->test(Shop::class)
        ->set('characterId', $ladro->id)
        ->call('preferisci', $grimaldelli->id);

    expect($ladro->favoriteItems()->count())->toBe(1)
        ->and($this->eroe->favoriteItems()->count())->toBe(0);
});
// L'ID arriva dal browser: il componente deve autorizzare il personaggio prima di modificarne i preferiti.
it('e un personaggio altrui non si tocca', function () {
    $altrui = Character::factory()->for(User::factory()->player())->create();
    $pozione = MarketItem::factory()->create();

    Livewire::actingAs($this->giocatore)
        ->test(Shop::class)
        ->set('characterId', $altrui->id)
        ->call('preferisci', $pozione->id)
        ->assertForbidden();

    expect($altrui->favoriteItems()->count())->toBe(0);
});


describe('sulla scheda del personaggio', function () {
    it('c\'è il pannello con i preferiti e il prezzo', function () {
        $this->eroe->favoriteItems()->attach(MarketItem::factory()->named('Corda di Seta', 25)->create());

        $this->actingAs($this->giocatore)
            ->get(route('characters.section', [$this->eroe, 'zaino']))
            ->assertOk()
            ->assertSee('Preferiti dell\'emporio')
            ->assertSee('Corda di Seta')
            ->assertSee('25 mo');
    });


    it('il collegamento apre l\'emporio sull\'articolo', function () {
        $corda = MarketItem::factory()->named('Corda di Seta')->create();
        $this->eroe->favoriteItems()->attach($corda);

        $this->actingAs($this->giocatore)
            ->get(route('characters.section', [$this->eroe, 'zaino']))
            ->assertSee(route('market.shop', ['oggetto' => $corda->id]), false);

        $this->actingAs($this->giocatore)
            ->get(route('market.shop', ['oggetto' => $corda->id]))
            ->assertOk()
            ->assertSee('Recupera 2d4+2 punti ferita')
            ->assertSee('Compra');
    });

    it('da lì si toglie la stella', function () {
        $corda = MarketItem::factory()->named('Corda di Seta')->create();
        $this->eroe->favoriteItems()->attach($corda);

        Livewire::actingAs($this->giocatore)
            ->test(Favorites::class, ['character' => $this->eroe])
            ->call('togli', $corda->id)
            ->assertDontSee('Corda di Seta');

        expect($this->eroe->favoriteItems()->count())->toBe(0);
    });
// Un preferito esaurito resta nella scheda perché l'indisponibilità è comunque un'informazione utile.
    it('un preferito esaurito resta, ma è segnato', function () {
        $this->eroe->favoriteItems()->attach(MarketItem::factory()->named('Pozione di Cura')->soldOut()->create());

        $this->actingAs($this->giocatore)
            ->get(route('characters.section', [$this->eroe, 'zaino']))
            ->assertSee('Pozione di Cura')
            ->assertSee('esaurito');
    });

// I preferiti fanno parte dello zaino privato e non vengono esposti nella vista pubblica di un altro personaggio.
    it('la lista di un altro non si vede', function () {
        $this->eroe->favoriteItems()->attach(MarketItem::factory()->named('Corda di Seta')->create());

        $curioso = User::factory()->player()->create();

        $this->actingAs($curioso)
            ->get(route('characters.section', [$this->eroe, 'zaino']))
            ->assertOk()
            ->assertSee('La sua vetrina')
            ->assertDontSee('Preferiti dell\'emporio')
            ->assertDontSee('Corda di Seta');

        Livewire::actingAs($curioso)
            ->test(Favorites::class, ['character' => $this->eroe])
            ->assertDontSee('Corda di Seta');
    });

    it('e chi non ne ha legge come si comincia', function () {
        $this->actingAs($this->giocatore)
            ->get(route('characters.section', [$this->eroe, 'zaino']))
            ->assertOk()
            ->assertSee('la stella accanto a un articolo lo mette qui');
    });
});
