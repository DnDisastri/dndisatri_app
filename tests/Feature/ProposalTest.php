<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RequestLevelUp;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\User;

// La proposta calcola il nuovo stato, ma la scheda cambia solo quando un DM la approva.
describe('passaggio di livello', function () {
    it('calcola i PF col metodo media', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 2, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 14, 'hp_max' => 20, 'hp_current' => 20,
        ]);

        $change = app(RequestLevelUp::class)->handle($character, $player);

        expect($change->diff['level'])->toBe(3)
            ->and($change->diff['hp_max'])->toBe(28)
            ->and($change->summary)->toContain('+8 PF');
    });
// L'ASI di Costituzione va applicato prima dei PF perché un nuovo modificatore produce anche PF retroattivi.
    it('applica l\'ASI PRIMA di calcolare i PF, e aggiunge i retroattivi', function () {

        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 7, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 15, 'hp_max' => 52, 'hp_current' => 52,
        ]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'plus2', asiAbilities: ['con']
        );

        expect($change->diff['con'])->toBe(17)
            ->and($change->diff['hp_max'])->toBe(68)
            ->and($change->summary)->toContain('+9 PF')
            ->and($change->summary)->toContain('+7 PF retroattivi');
    });

    it('non dà retroattivi se il modificatore non scatta', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 7, 'hit_die' => 8, 'con' => 12, 'hp_max' => 40, 'hp_current' => 40,
        ]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'plus1', asiAbilities: ['con', 'str']
        );

        expect($change->diff['con'])->toBe(13)
            ->and($change->summary)->not->toContain('retroattivi');
    });

    it('non supera il tetto di 20 nei punteggi', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 7, 'str' => 19, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 14,
        ]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'plus2', asiAbilities: ['str']
        );

        expect($change->diff['str'])->toBe(20);
    });

    it('rifiuta un +1/+1 sulla stessa caratteristica', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['level' => 3]);

        expect(fn () => app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'plus1', asiAbilities: ['str', 'str']
        ))->toThrow(InvalidArgumentException::class);
    });

    it('non va oltre il ventesimo livello', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['level' => 20]);

        expect(fn () => app(RequestLevelUp::class)->handle($character, $player))
            ->toThrow(InvalidArgumentException::class);
    });

    it('col talento invece dell\'ASI, lo crea all\'approvazione', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 3, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 14, 'str' => 16,
        ]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'feat',
            featName: 'Attaccabrighe', featDescription: 'Attacco bonus con l\'arma secondaria.',
        );

        expect($change->diff['str'])->toBe(16)
            ->and($change->summary)->toContain('Talento: Attaccabrighe');

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $feat = $character->fresh()->feats()->first();

        expect($feat->name)->toBe('Attaccabrighe')
            ->and($feat->level)->toBe(4)
            ->and($feat->source)->toBe('asi');
    });

    it('il tutto arriva alla scheda quando il DM approva', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 7, 'class' => 'Guerriero', 'hit_die' => 10, 'con' => 15, 'hp_max' => 52, 'hp_current' => 52,
        ]);

        $change = app(RequestLevelUp::class)->handle(
            $character, $player, asiMode: 'plus2', asiAbilities: ['con']
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $character->refresh();

        expect($character->level)->toBe(8)
            ->and($character->con)->toBe(17)
            ->and($character->hp_max)->toBe(68)
            ->and($character->proficiencyBonus())->toBe(3);
    });
});

describe('modifica della scheda', function () {
    it('propone solo quello che è davvero cambiato', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'name' => 'Elandra', 'notes' => 'vecchie', 'background' => 'Accolito',
        ]);

        $change = app(ProposeChange::class)->edit($character, $player, [
            'name' => 'Elandra',         
            'notes' => 'nuove',           
            'background' => 'Accolito',  
        ]);

        expect($change->diff)->toBe(['notes' => 'nuove'])
            ->and($change->type)->toBe(PendingChangeType::CharacterEdit);
    });

    it('rifiuta un modulo rimandato senza modifiche', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['notes' => 'uguali']);

        expect(fn () => app(ProposeChange::class)->edit($character, $player, ['notes' => 'uguali']))
            ->toThrow(InvalidArgumentException::class);
    });
// I campi composti vengono confrontati per contenuto e non per ordine.
    it('confronta i campi composti per contenuto', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'skills' => ['stealth' => 'proficient', 'arcana' => 'none'],
        ]);

        expect(fn () => app(ProposeChange::class)->edit($character, $player, [
            'skills' => ['arcana' => 'none', 'stealth' => 'proficient'],
        ]))->toThrow(InvalidArgumentException::class);
    });
// La proposta conserva lo stato di partenza per rilevare modifiche concorrenti prima dell'approvazione.
    it('registra com\'era la scheda al momento della proposta', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['notes' => 'a']);

        $change = app(ProposeChange::class)->edit($character, $player, ['notes' => 'b']);

        expect($change->isStale())->toBeFalse();

        $this->travel(1)->hours();
        $character->touch();

        expect($change->fresh()->load('character')->isStale())->toBeTrue();
    });
});

describe('bottino', function () {
    it('si registra con oro e oggetti', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['gp' => 10]);

        $change = app(ProposeChange::class)->loot($character, $player, 150, [
            ['name' => 'Spada Lunga', 'qty' => 1, 'category' => 'Armi', 'value' => 15],
        ], 'Drago rosso');

        expect($change->grant_gp)->toBe(150)
            ->and($change->summary)->toContain('150 mo')
            ->and($change->summary)->toContain('Drago rosso');

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->fresh()->gp)->toBe(160)
            ->and($character->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });

    it('non si registra vuoto', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();

        expect(fn () => app(ProposeChange::class)->loot($character, $player))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('oggetto magico', function () {
    it('diventa un effetto sui punteggi quando approvato', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['str' => 10]);

        $change = app(ProposeChange::class)->itemEffect(
            $character, $player, 'Cintura di Forza del Gigante',
            Ability::Str, ItemEffectMode::Set, 21
        );

        expect($change->summary)->toContain('FOR porta a21');

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $character->refresh()->load('itemEffects', 'items');

        expect($character->effectiveScores()->score(Ability::Str))->toBe(21);
    });
});
