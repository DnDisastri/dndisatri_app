<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\CreateCharacter;
use App\Actions\Characters\EquipItem;
use App\Actions\Characters\ProposeChange;
use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Domain\Dnd\ClassRules;
use App\Enums\EquipmentSlot;
use App\Filament\Resources\PendingChanges\Pages\ViewPendingChange;
use App\Livewire\InventoryManager;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->pg = Character::factory()->create(['dex' => 14, 'str' => 10, 'gp' => 100]);
});

function adamantina(Character $pg, array $attributi = []): CharacterItem
{
    return CharacterItem::factory()->for($pg)->create([
        'name' => 'Adamantine Armor',
        'base' => 'Armatura a Piastre',
        'category' => 'Armature',
        'value_cp' => 50_000,
        ...$attributi,
    ]);
}

describe('un oggetto con un nome suo e una base', function () {
    it('si indossa come la sua base e ne prende la CA', function () {
        $armatura = adamantina($this->pg);

        expect($armatura->naturalSlot())->toBe(EquipmentSlot::Armor);

        app(EquipItem::class)->equip($armatura);

        expect($this->pg->fresh()->armorClass())->toBe(18);
    });

    it('somma il bonus magico di armatura e scudo', function () {
        adamantina($this->pg, ['magic_bonus' => 1, 'equipped_slot' => EquipmentSlot::Armor]);
        CharacterItem::factory()->for($this->pg)->create([
            'name' => 'Scudo del Drago', 'base' => 'Scudo', 'magic_bonus' => 1, 'equipped_slot' => EquipmentSlot::Shield,
        ]);

        expect($this->pg->fresh()->armorClass())->toBe(18 + 1 + 2 + 1);
    });

    it('senza base resta com\'era: nello zaino, senza effetto sulla CA', function () {
        $vecchia = CharacterItem::factory()->for($this->pg)->create(['name' => 'Adamantine Armor']);

        expect($vecchia->naturalSlot())->toBeNull();
        expect(fn () => app(EquipItem::class)->equip($vecchia))->toThrow(RuntimeException::class);
    });

    it('un\'arma con base e bonus compare fra gli attacchi', function () {
        CharacterItem::factory()->for($this->pg)->create([
            'name' => 'Pungiglione', 'base' => 'Spada Corta', 'magic_bonus' => 1,
        ]);

        $attacco = $this->pg->fresh()->attacks()->firstWhere('name', 'Pungiglione');

        expect($attacco)->not->toBeNull()
            ->and($attacco['damage'])->toBe('1d6+3')
            ->and($attacco['attack'])->toBe(2 + 2 + 1);
    });
});

describe('staccare un pezzo dalla pila', function () {
    it('tiene valore, base e bonus', function () {
        $pila = CharacterItem::factory()->for($this->pg)->create([
            'name' => 'Pugnale d\'Argento', 'base' => 'Pugnale', 'magic_bonus' => 1, 'qty' => 2, 'value_cp' => 2_500,
        ]);

        $impugnato = app(EquipItem::class)->equip($pila);

        expect($impugnato->qty)->toBe(1)
            ->and($impugnato->value_cp)->toBe(2_500)
            ->and($impugnato->base)->toBe('Pugnale')
            ->and($impugnato->magic_bonus)->toBe(1)
            ->and($pila->fresh()->qty)->toBe(1);
    });

    it('alla creazione il ladro impugna un pugnale e tiene l\'altro nello zaino', function () {
        $ladro = app(CreateCharacter::class)->handle(...[
            'owner' => User::factory()->player()->create(),
            'name' => 'Vesper',
            'class' => 'Ladro',
            'species' => 'Mezzorco',
            'background' => 'Soldato',
            'boughtScores' => ['str' => 8, 'dex' => 15, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 10],
            'skills' => collect(ClassRules::skillChoices('Ladro'))
                ->reject(fn ($s) => in_array($s, ['athletics', 'intimidation'], true))
                ->take(4)->values()->all(),
        ]);

        $pugnali = $ladro->items()->where('name', 'Pugnale')->get();

        expect($pugnali->firstWhere('equipped_slot', EquipmentSlot::Weapon)?->qty)->toBe(1)
            ->and($pugnali->whereNull('equipped_slot')->first()?->qty)->toBe(1);
    });
});

describe('passando di mano', function () {
    it('un annuncio venduto consegna base e bonus', function () {
        adamantina($this->pg, ['magic_bonus' => 1]);
        $compratore = Character::factory()->create(['gp' => 1000]);

        $annuncio = app(CreateListing::class)->handle($this->pg, 'Adamantine Armor', 1, 10_000);
        app(BuyListing::class)->handle($annuncio, $compratore);

        $arrivata = $compratore->items()->where('name', 'Adamantine Armor')->first();

        expect($arrivata->base)->toBe('Armatura a Piastre')
            ->and($arrivata->magic_bonus)->toBe(1)
            ->and($arrivata->value_cp)->toBe(50_000);
    });

    it('uno scambio accettato consegna base e bonus', function () {
        adamantina($this->pg, ['magic_bonus' => 2]);
        $altro = Character::factory()->create();

        $scambio = app(CreateTrade::class)->handle($this->pg, $altro, give: [['name' => 'Adamantine Armor']]);
        app(AcceptTrade::class)->handle($scambio);

        $arrivata = $altro->items()->where('name', 'Adamantine Armor')->first();

        expect($arrivata->base)->toBe('Armatura a Piastre')
            ->and($arrivata->magic_bonus)->toBe(2);
    });
});

describe('il bottino', function () {
    beforeEach(function () {
        $this->dm = User::factory()->dm()->create();
        $this->giocatore = $this->pg->user;
    });

    it('il giocatore propone il tipo e la categoria dalla lista', function () {
        $this->actingAs($this->giocatore)
            ->post(route('proposals.loot', $this->pg), [
                'items' => [['name' => 'Adamantine Armor', 'category' => 'Armature', 'base' => 'Armatura a Piastre']],
            ])
            ->assertSessionHasNoErrors();

        expect($this->pg->pendingChanges()->latest('id')->first()->grant_items[0]['base'])->toBe('Armatura a Piastre');
    });

    it('rifiuta categorie e tipi fuori elenco', function () {
        $this->actingAs($this->giocatore)
            ->post(route('proposals.loot', $this->pg), [
                'items' => [['name' => 'Spada', 'category' => 'Roba', 'base' => 'Spada Laser']],
            ])
            ->assertSessionHasErrors(['items.0.category', 'items.0.base']);
    });

    it('il DM corregge tipo e bonus prima di approvare, e lo storico lo ricorda', function () {
        $change = app(ProposeChange::class)->loot($this->pg, $this->giocatore, items: [
            ['name' => 'Adamantine Armor', 'qty' => 1, 'base' => 'Mezza Armatura'],
            ['name' => 'Corda', 'qty' => 1],
        ]);

        $this->actingAs($this->dm);

        Livewire::test(ViewPendingChange::class, ['record' => $change->getRouteKey()])
            ->callAction('approva', [
                'oggetti' => [
                    0 => ['base' => 'Armatura a Piastre', 'magic_bonus' => 1],
                    1 => ['base' => null, 'magic_bonus' => 0],
                ],
            ])
            ->assertHasNoActionErrors();

        $armatura = $this->pg->items()->where('name', 'Adamantine Armor')->first();

        expect($armatura->base)->toBe('Armatura a Piastre')
            ->and($armatura->magic_bonus)->toBe(1)
            ->and($change->fresh()->grant_items[0]['base'])->toBe('Armatura a Piastre');
    });

    it('un tipo inventato o un bonus esagerato non passano', function () {
        $change = app(ProposeChange::class)->loot($this->pg, $this->giocatore, items: [['name' => 'Bastone', 'qty' => 1]]);

        app(ApprovePendingChange::class)->handle($change, $this->dm, null, [
            0 => ['base' => 'Spada Laser', 'magic_bonus' => 9],
        ]);

        $bastone = $this->pg->items()->where('name', 'Bastone')->first();

        expect($bastone->base)->toBeNull()
            ->and($bastone->magic_bonus)->toBe(CharacterItem::MAX_MAGIC_BONUS);
    });

    it('la pagina offre catalogo, negozio e oggetti già approvati', function () {
        $passato = app(ProposeChange::class)->loot($this->pg, $this->giocatore, items: [['name' => 'Corno di Valhalla', 'qty' => 1]]);
        app(ApprovePendingChange::class)->handle($passato, $this->dm);

        $this->actingAs($this->giocatore)
            ->get(route('proposals.loot', $this->pg))
            ->assertOk()
            ->assertViewHas('suggerimenti', fn (array $voci) => collect($voci)->pluck('source', 'name')->only([
                'Corno di Valhalla', 'Armatura a Piastre',
            ])->all() === ['Armatura a Piastre' => 'catalogo', 'Corno di Valhalla' => 'già trovato']);
    });
});

describe('la modifica dallo zaino', function () {
    it('un DM assegna tipo e bonus', function () {
        $armatura = CharacterItem::factory()->for($this->pg)->create(['name' => 'Adamantine Armor']);

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(InventoryManager::class, ['character' => $this->pg])
            ->call('apriModifica', $armatura->id)
            ->set('modifica.base', 'Armatura a Piastre')
            ->set('modifica.magic_bonus', 1)
            ->set('modifica.category', 'Armature')
            ->call('salvaModifica')
            ->assertHasNoErrors();

        expect($armatura->fresh()->base)->toBe('Armatura a Piastre')
            ->and($armatura->fresh()->magic_bonus)->toBe(1)
            ->and($armatura->fresh()->category)->toBe('Armature');
    });

    it('il giocatore non può', function () {
        $armatura = CharacterItem::factory()->for($this->pg)->create(['name' => 'Adamantine Armor']);

        Livewire::actingAs($this->pg->user)
            ->test(InventoryManager::class, ['character' => $this->pg])
            ->call('apriModifica', $armatura->id)
            ->assertForbidden();
    });

    it('togliendo il tipo a un\'armatura indossata, torna nello zaino', function () {
        $armatura = adamantina($this->pg, ['equipped_slot' => EquipmentSlot::Armor]);

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(InventoryManager::class, ['character' => $this->pg])
            ->call('apriModifica', $armatura->id)
            ->set('modifica.base', '')
            ->call('salvaModifica')
            ->assertHasNoErrors();

        expect($armatura->fresh()->isEquipped())->toBeFalse()
            ->and($armatura->fresh()->base)->toBeNull();
    });
});

describe('il comando che separa i dettagli', function () {
    beforeEach(function () {
        $this->lunga = CharacterItem::factory()->for($this->pg)->create([
            'name' => 'Adamantine Armor - Nonostante sia nuova, ha già preso abbastanza colpi...',
        ]);
        $this->altra = CharacterItem::factory()->for($this->pg)->create(['name' => 'Anello - Regalo di Orcus']);
    });

    it('in prova non salva niente', function () {
        $this->artisan('dndisastri:separa-dettagli', ['--dry-run' => true])
            ->expectsOutputToContain('Prova')
            ->assertSuccessful();

        expect($this->lunga->fresh()->name)->toStartWith('Adamantine Armor - ');
    });

    it('sposta la descrizione nei dettagli, solo per gli oggetti indicati', function () {
        $this->artisan('dndisastri:separa-dettagli', ['--id' => [$this->lunga->id]])->assertSuccessful();

        expect($this->lunga->fresh()->name)->toBe('Adamantine Armor')
            ->and($this->lunga->fresh()->details)->toBe('Nonostante sia nuova, ha già preso abbastanza colpi...')
            ->and($this->altra->fresh()->name)->toBe('Anello - Regalo di Orcus');
    });
});
