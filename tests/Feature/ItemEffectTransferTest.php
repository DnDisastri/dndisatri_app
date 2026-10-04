<?php

declare(strict_types=1);

use App\Actions\Characters\AttuneItem;
use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CancelListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Actions\Market\ReverseTransaction;
use App\Domain\Dnd\Ability;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;

// L'effetto è legato alla riga dello zaino: senza la copia sparirebbe a ogni passaggio di mano.
beforeEach(function () {
    $this->venditore = Character::factory()->create(['str' => 10]);
    $this->compratore = Character::factory()->create(['str' => 10, 'gp' => 1_000]);

    $this->anello = CharacterItem::factory()->for($this->venditore)->create([
        'name' => 'Anello della Forza', 'category' => 'Oggetti Magici', 'value_cp' => 10_000,
    ]);
    $this->venditore->itemEffects()->create([
        'character_item_id' => $this->anello->id, 'name' => 'Anello della Forza', 'ability' => 'str', 'mode' => 'bonus', 'value' => 2,
    ]);
    app(AttuneItem::class)->attune($this->anello);
});

function anelloDi(Character $pg): ?CharacterItem
{
    return $pg->items()->where('name', 'Anello della Forza')->first();
}

it('venduto con un annuncio arriva col suo effetto, da sintonizzare', function () {
    $annuncio = app(CreateListing::class)->handle($this->venditore, 'Anello della Forza', 1, 5_000);

    expect($this->venditore->fresh()->effectiveScores()->score(Ability::Str))->toBe(10);

    app(BuyListing::class)->handle($annuncio, $this->compratore);

    $anello = anelloDi($this->compratore);
    expect($anello->effects()->count())->toBe(1)
        ->and($anello->attuned)->toBeFalse();

    app(AttuneItem::class)->attune($anello);

    expect($this->compratore->fresh()->effectiveScores()->score(Ability::Str))->toBe(12);
});

it('un annuncio ritirato restituisce l\'effetto', function () {
    $annuncio = app(CreateListing::class)->handle($this->venditore, 'Anello della Forza', 1, 5_000);
    app(CancelListing::class)->handle($annuncio);

    expect(anelloDi($this->venditore)->effects()->count())->toBe(1);
});

it('uno scambio porta l\'effetto, e annullarlo lo riporta indietro', function () {
    $scambio = app(CreateTrade::class)->handle($this->venditore, $this->compratore, give: [['name' => 'Anello della Forza']]);
    app(AcceptTrade::class)->handle($scambio);

    expect(anelloDi($this->compratore)->effects()->count())->toBe(1)
        ->and(anelloDi($this->venditore))->toBeNull();

    app(ReverseTransaction::class)->trade($scambio, User::factory()->admin()->create(), 'prova');

    expect(anelloDi($this->venditore)->effects()->count())->toBe(1)
        ->and(anelloDi($this->compratore))->toBeNull();
});

it('non si accorpa a un oggetto con lo stesso nome ma senza effetto', function () {
    CharacterItem::factory()->for($this->compratore)->create(['name' => 'Anello della Forza']);
    $annuncio = app(CreateListing::class)->handle($this->venditore, 'Anello della Forza', 1, 5_000);

    app(BuyListing::class)->handle($annuncio, $this->compratore);

    $anelli = $this->compratore->items()->where('name', 'Anello della Forza')->withCount('effects')->get();

    expect($anelli)->toHaveCount(2)
        ->and($anelli->pluck('effects_count')->sort()->values()->all())->toBe([0, 1]);
});
