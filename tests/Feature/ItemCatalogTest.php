<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\LootSuggestions;
use App\Actions\Characters\ProposeChange;
use App\Actions\Sessions\AwardSessionRewards;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ArmorClass;
use App\Domain\Dnd\Coins;
use App\Enums\EquipmentSlot;
use App\Filament\Resources\BaseItems\BaseItemResource;
use App\Filament\Resources\BaseItems\Pages\ListBaseItems;
use App\Filament\Resources\MagicItems\MagicItemResource;
use App\Filament\Resources\ManualPrices\ManualPriceResource;
use App\Models\BaseItem;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\MagicItem;
use App\Models\ManualPrice;
use App\Models\User;
use Livewire\Livewire;

describe('armi, armature e scudi', function () {
    it('arrivano con la migrazione', function () {
        expect(BaseItem::count())->toBe(26)
            ->and(EquipmentSlot::isBase('Armatura a Piastre'))->toBeTrue()
            ->and(EquipmentSlot::bases()['Armi'])->toHaveKey('Spada Lunga')->not->toHaveKey('Arma +1');
    });

    it('la CA segue i numeri del pannello', function () {
        $scores = AbilityScores::fromArray(['dex' => 16]);

        expect(ArmorClass::compute($scores, 'Corazza di Scaglie'))->toBe(16);

        BaseItem::firstWhere('name', 'Corazza di Scaglie')->update(['armor_base' => 15, 'dex_cap' => 3]);

        expect(ArmorClass::compute($scores, 'Corazza di Scaglie'))->toBe(18);
    });

    it('un\'arma nuova dal pannello si impugna e attacca', function () {
        BaseItem::create(['name' => 'Stocco', 'kind' => 'weapon', 'damage' => '1d8', 'damage_type' => 'piercing', 'ability' => 'str', 'properties' => ['Accurata']]);
        $character = Character::factory()->create(['str' => 10, 'dex' => 18]);
        $stocco = $character->addToInventory(name: 'Stocco');

        expect($stocco->naturalSlot())->toBe(EquipmentSlot::Weapon);

        $attacco = $character->fresh()->attacks()->firstWhere('name', 'Stocco');

        // Accurata: con più Destrezza che Forza, vale la Destrezza.
        expect($attacco['ability']->value)->toBe('dex')
            ->and($attacco['damage'])->toBe('1d8+4')
            ->and($attacco['damage_type'])->toBe('perforanti');
    });

    it('rinominandola, gli oggetti che la usano la seguono', function () {
        $character = Character::factory()->create();
        $spada = $character->addToInventory(name: 'Spada Lunga');
        $lama = $character->addToInventory(name: 'Lama del Nord', base: 'Spada Lunga');

        BaseItem::firstWhere('name', 'Spada Lunga')->update(['name' => 'Spada Bastarda']);

        expect($spada->fresh()->catalogKey())->toBe('Spada Bastarda')
            ->and($lama->fresh()->base)->toBe('Spada Bastarda')
            ->and($character->fresh()->attacks()->firstWhere('name', 'Lama del Nord')['damage'])->toStartWith('1d8');
    });

    it('quella che qualcuno usa non si cancella', function () {
        Character::factory()->create()->addToInventory(name: 'Mazza');
        $libera = BaseItem::firstWhere('name', 'Alabarda');

        Livewire::actingAs(User::factory()->dm()->create())
            ->test(ListBaseItems::class)
            ->assertTableActionHidden('delete', BaseItem::firstWhere('name', 'Mazza'))
            ->assertTableActionVisible('delete', $libera);
    });
});

describe('gli oggetti magici del catalogo', function () {
    beforeEach(function () {
        $this->guanti = MagicItem::firstWhere('name', "Guanti della Potenza dell'Orco");
        $this->character = Character::factory()->create(['str' => 12]);
    });

    it('si cercano nel bottino, con il loro id', function () {
        $voce = collect(app(LootSuggestions::class)->handle())->firstWhere('name', $this->guanti->name);

        expect($voce['magic_item_id'])->toBe($this->guanti->id)
            ->and($voce['source'])->toBe('oggetto magico')
            ->and($voce['details'])->toContain('richiede sintonia');
    });

    it('approvato, l\'oggetto porta i suoi effetti', function () {
        $change = app(ProposeChange::class)->loot($this->character, $this->character->user, items: [
            ['name' => $this->guanti->name, 'qty' => 1, 'magic_item_id' => (string) $this->guanti->id],
        ]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $oggetto = $this->character->items()->firstWhere('name', $this->guanti->name);
        $oggetto->forceFill(['attuned' => true])->save();

        expect($oggetto->effects()->count())->toBe(1)
            ->and($this->character->fresh()->effectiveScores()->toArray()['str'])->toBe(19);
    });

    it('un id che non esiste si scarta', function () {
        $change = app(ProposeChange::class)->loot($this->character, $this->character->user, items: [
            ['name' => 'Sasso', 'magic_item_id' => '99999'],
        ]);

        expect($change->grant_items[0])->not->toHaveKey('magic_item_id');
    });

    it('il DM può riconoscerlo lui, e tipo e bonus arrivano dal catalogo', function () {
        $scudo = MagicItem::firstWhere('name', 'Scudo +1');
        $change = app(ProposeChange::class)->loot($this->character, $this->character->user, items: [['name' => 'Scudo lucente']]);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create(), itemFixes: [
            ['magic_item_id' => $scudo->id, 'base' => null, 'magic_bonus' => 0],
        ]);

        expect($this->character->items()->firstWhere('name', 'Scudo lucente'))
            ->base->toBe('Scudo')
            ->magic_bonus->toBe(1);
    });

    it('anche nelle ricompense di fine sessione', function () {
        $sessione = GameSession::factory()->for(Campaign::factory())->create(['played_at' => now()->subDay()]);
        $sessione->attendees()->attach($this->character->user_id, ['character_id' => $this->character->id]);

        app(AwardSessionRewards::class)->handle(
            $sessione, User::factory()->dm()->create(), Coins::none(), 'Tesoro del drago',
            items: [['name' => $this->guanti->name, 'magic_item_id' => $this->guanti->id]],
        );

        expect($this->character->items()->firstWhere('name', $this->guanti->name)->effects()->count())->toBe(1);
    });
});

describe('il listino del Manuale', function () {
    it('il Manuale legge le voci del pannello, con la percentuale della campagna', function () {
        $dm = User::factory()->dm()->create();
        Campaign::factory()->create(['slug' => 'valcupa', 'dm_id' => $dm->id, 'price_modifier' => 50]);
        ManualPrice::create(['category' => 'Servizi', 'name' => 'Messaggero a cavallo', 'price_cp' => 1_000]);

        $this->actingAs($dm)
            ->get(route('dm.manual', ['campagna' => 'valcupa']))
            ->assertOk()
            ->assertSee('Messaggero a cavallo')
            ->assertSee('15 mo');
    });
});

describe('le pagine del pannello', function () {
    it('le aprono DM e admin', function (string $risorsa, string $ruolo) {
        $this->actingAs(User::factory()->{$ruolo}()->create())
            ->get($risorsa::getUrl('index'))
            ->assertOk();
    })->with([BaseItemResource::class, MagicItemResource::class, ManualPriceResource::class])->with(['dm', 'admin']);

    it('non i giocatori', function (string $risorsa) {
        $this->actingAs(User::factory()->player()->create())
            ->get($risorsa::getUrl('index'))
            ->assertForbidden();
    })->with([BaseItemResource::class, MagicItemResource::class, ManualPriceResource::class]);
});
