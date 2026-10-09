<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Actions\Market\BuyFromShop;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Exceptions\MarketException;
use App\Filament\Resources\MarketItems\Pages\ListMarketItems;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Livewire\Market\Shop;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\MarketItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->pg = Character::factory()->ownedBy($this->giocatore)->create();
    $this->dm = User::factory()->dm()->create();

    $this->armatura = CharacterItem::factory()->for($this->pg)->create([
        'name' => 'Adamantine Armor',
        'base' => 'Armatura a Piastre',
        'magic_bonus' => 1,
        'category' => 'Armature',
        'value_cp' => 50_000,
        'details' => 'Ha già preso parecchi colpi.',
    ]);

    $this->spada = MarketItem::factory()->named('Spada Lunga', 15)->stock(3)->create(['category' => 'Armi']);
});

function proponi(Character $pg, User $chi, CharacterItem $dà, MarketItem $prende)
{
    return app(ProposeChange::class)->barter($pg, $chi, $dà, $prende);
}

describe('la proposta', function () {
    it('dal negozio diventa una richiesta, e non sposta niente', function () {
        Livewire::actingAs($this->giocatore)
            ->test(Shop::class)
            ->call('apri', $this->spada->id)
            ->set('offerta', $this->armatura->id)
            ->call('barter', $this->spada->id)
            ->assertHasNoErrors();

        $richiesta = $this->pg->pendingChanges()->first();

        expect($richiesta->type)->toBe(PendingChangeType::Barter)
            ->and($richiesta->summary)->toBe('Baratto: Adamantine Armor per Spada Lunga')
            ->and($this->armatura->fresh())->not->toBeNull()
            ->and($this->spada->fresh()->stock)->toBe(3);
    });

    it('offre solo gli oggetti che valgono almeno il prezzo', function () {
        CharacterItem::factory()->for($this->pg)->create(['name' => 'Corda', 'value_cp' => 100]);

        Livewire::actingAs($this->giocatore)
            ->test(Shop::class)
            ->call('apri', $this->spada->id)
            ->assertViewHas('offribili', fn ($oggetti) => $oggetti->pluck('name')->all() === ['Adamantine Armor']);
    });

    it('rifiuta un oggetto che vale meno dell\'articolo', function () {
        $corda = CharacterItem::factory()->for($this->pg)->create(['name' => 'Corda', 'value_cp' => 100]);

        expect(fn () => proponi($this->pg, $this->giocatore, $corda, $this->spada))
            ->toThrow(InvalidArgumentException::class, 'non basta');
    });

    it('un oggetto magico porta il suo effetto in magazzino, e a chi poi lo compra', function () {
        $this->pg->itemEffects()->create([
            'character_item_id' => $this->armatura->id, 'name' => 'Adamantine Armor', 'ability' => 'str', 'mode' => 'bonus', 'value' => 1,
        ]);

        app(ApprovePendingChange::class)->handle(proponi($this->pg, $this->giocatore, $this->armatura, $this->spada), $this->dm);

        $magazzino = MarketItem::inStorage()->sole();
        expect($magazzino->effects)->toBe([['ability' => 'str', 'mode' => 'bonus', 'value' => 1]])
            ->and($this->pg->itemEffects()->count())->toBe(0);

        $magazzino->forceFill(['in_storage' => false])->save();

        Livewire::actingAs($this->giocatore)
            ->test(Shop::class)
            ->call('apri', $magazzino->id)
            ->assertSee('Effetto magico, con la sintonia: FOR +1');

        $altro = Character::factory()->create(['gp' => 1_000]);
        app(BuyFromShop::class)->handle($altro, $magazzino);

        $arrivata = $altro->items()->where('name', 'Adamantine Armor')->sole();

        expect($arrivata->effects()->sole()->describe())->toBe('Adamantine Armor: FOR +1')
            ->and($arrivata->attuned)->toBeFalse();
    });

    it('non lascia offrire due volte lo stesso oggetto', function () {
        proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);

        expect(fn () => proponi($this->pg, $this->giocatore, $this->armatura, $this->spada))
            ->toThrow(InvalidArgumentException::class, 'già offerto');
    });

    it('non vale per gli oggetti in magazzino', function () {
        $magazzino = MarketItem::factory()->inStorage()->create();

        expect(fn () => proponi($this->pg, $this->giocatore, $this->armatura, $magazzino))
            ->toThrow(InvalidArgumentException::class, 'non è disponibile');
    });
});

describe('l\'approvazione', function () {
    it('scambia gli oggetti, scala le scorte e mette quello del giocatore in magazzino', function () {
        $richiesta = proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);

        app(ApprovePendingChange::class)->handle($richiesta, $this->dm);

        $magazzino = MarketItem::inStorage()->sole();

        expect($this->pg->items()->where('name', 'Adamantine Armor')->exists())->toBeFalse()
            ->and($this->pg->items()->where('name', 'Spada Lunga')->first()?->value_cp)->toBe(1_500)
            ->and($this->spada->fresh()->stock)->toBe(2)
            ->and($magazzino->only(['name', 'base', 'magic_bonus', 'category', 'details', 'price_cp', 'stock']))->toBe([
                'name' => 'Adamantine Armor',
                'base' => 'Armatura a Piastre',
                'magic_bonus' => 1,
                'category' => 'Armature',
                'details' => 'Ha già preso parecchi colpi.',
                'price_cp' => 50_000,
                'stock' => 1,
            ])
            ->and($magazzino->isAvailable())->toBeFalse()
            ->and($richiesta->fresh()->status)->toBe(PendingChangeStatus::Approved);
    });

    it('un articolo illimitato non scala', function () {
        $pozione = MarketItem::factory()->unlimited()->create();
        app(ApprovePendingChange::class)->handle(proponi($this->pg, $this->giocatore, $this->armatura, $pozione), $this->dm);

        expect($pozione->fresh()->stock)->toBe(0)
            ->and($this->pg->items()->where('name', 'Pozione di Cura')->exists())->toBeTrue();
    });

    it('si ferma se l\'articolo è finito nel frattempo, senza toccare niente', function () {
        $richiesta = proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);
        $this->spada->update(['stock' => 0]);

        expect(fn () => app(ApprovePendingChange::class)->handle($richiesta, $this->dm))
            ->toThrow(RuntimeException::class, 'non è più disponibile');

        expect($this->armatura->fresh())->not->toBeNull()
            ->and(MarketItem::inStorage()->exists())->toBeFalse()
            ->and($richiesta->fresh()->isPending())->toBeTrue();
    });

    it('si ferma se il giocatore non ha più l\'oggetto', function () {
        $richiesta = proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);
        $this->armatura->delete();

        expect(fn () => app(ApprovePendingChange::class)->handle($richiesta, $this->dm))
            ->toThrow(RuntimeException::class, 'non ha più');

        expect($this->spada->fresh()->stock)->toBe(3);
    });

    it('si ferma se nel frattempo l\'articolo costa più dell\'oggetto', function () {
        $richiesta = proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);
        $this->spada->update(['price_cp' => 60_000]);

        expect(fn () => app(ApprovePendingChange::class)->handle($richiesta, $this->dm))
            ->toThrow(RuntimeException::class, 'costa più');
    });

    it('il DM vede cosa entra e cosa esce, e se si può ancora fare', function () {
        $richiesta = proponi($this->pg, $this->giocatore, $this->armatura, $this->spada);

        $this->actingAs($this->dm)
            ->get(PendingChangeResource::getUrl('view', ['record' => $richiesta]))
            ->assertOk()
            ->assertSeeText('Baratto con l\'Emporio')
            ->assertSee('Adamantine Armor')
            ->assertSee('entra nel magazzino');

        $this->spada->update(['stock' => 0]);

        $this->actingAs($this->dm)
            ->get(PendingChangeResource::getUrl('view', ['record' => $richiesta]))
            ->assertSeeText('non è più disponibile nell\'Emporio');
    });
});

describe('il magazzino', function () {
    beforeEach(function () {
        $this->magazzino = MarketItem::factory()->inStorage()->create([
            'name' => 'Adamantine Armor', 'base' => 'Armatura a Piastre', 'magic_bonus' => 1, 'price_cp' => 50_000,
        ]);
    });

    it('non si vede nel negozio e non si compra', function () {
        Livewire::actingAs($this->giocatore)
            ->test(Shop::class)
            ->assertViewHas('items', fn ($items) => ! $items->contains('id', $this->magazzino->id))
            ->call('apri', $this->magazzino->id)
            ->assertSet('aperto', null);

        expect(fn () => app(BuyFromShop::class)->handle($this->pg, $this->magazzino))
            ->toThrow(MarketException::class);
    });

    it('un DM lo mette in vendita scegliendo il prezzo', function () {
        Livewire::actingAs($this->dm)
            ->test(ListMarketItems::class)
            ->assertSet('activeTab', 'magazzino')
            ->callTableAction('mettiInVendita', $this->magazzino, ['prezzo' => 450])
            ->assertHasNoTableActionErrors();

        expect($this->magazzino->fresh()->in_storage)->toBeFalse()
            ->and($this->magazzino->fresh()->price_cp)->toBe(45_000);
    });

    it('chi lo compra lo riceve con tipo e bonus', function () {
        $this->magazzino->forceFill(['in_storage' => false])->save();
        $this->pg->forceFill(['gp' => 1_000])->save();
        $this->armatura->delete();

        Livewire::actingAs($this->giocatore)
            ->test(Shop::class)
            ->call('apri', $this->magazzino->id)
            ->assertSee('Tipo: Armatura a Piastre +1');

        app(BuyFromShop::class)->handle($this->pg, $this->magazzino);

        $comprata = $this->pg->items()->where('name', 'Adamantine Armor')->latest('id')->first();

        expect($comprata->base)->toBe('Armatura a Piastre')
            ->and($comprata->magic_bonus)->toBe(1);
    });

    it('un articolo già in vendita non si rimette in vendita da un DM', function () {
        expect($this->dm->can('putOnSale', $this->spada))->toBeFalse()
            ->and($this->dm->can('putOnSale', $this->magazzino))->toBeTrue()
            ->and($this->giocatore->can('putOnSale', $this->magazzino))->toBeFalse();
    });
});
