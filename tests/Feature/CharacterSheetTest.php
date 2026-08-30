<?php

declare(strict_types=1);

use App\Domain\Dnd\Ability;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterItemEffect;
use Illuminate\Database\UniqueConstraintViolationException;


function sheet(array $attributes = []): Character
{
    return Character::factory()->create($attributes);
}

describe('classe armatura', function () {
    it('senza armatura è 10 più Destrezza', function () {
        $character = sheet(['dex' => 16]);

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(13);
    });

    it('tiene conto dell\'armatura indossata', function () {
        $character = sheet(['dex' => 16]);
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(16);
    });

    it('somma lo scudo', function () {
        $character = sheet(['dex' => 16]);
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();
        CharacterItem::factory()->for($character)->shield('Scudo +1')->create();

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(19);
    });

    it('ignora l\'armatura che il personaggio ha in zaino ma non indossa', function () {

        $character = sheet(['dex' => 16]);
        CharacterItem::factory()->for($character)->named('Armatura a Piastre', 'Armature')->create();

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(13);
    });

    it('cambia da sola se l\'armatura viene venduta', function () {
        $character = sheet(['dex' => 16]);
        $armor = CharacterItem::factory()->for($character)->armor('Armatura a Piastre')->create();

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(18);

        $armor->delete();

        expect($character->fresh()->load('items', 'itemEffects')->armorClass())->toBe(13);
    });
});

describe('un oggetto solo per slot', function () {
    it('il database rifiuta due armature indossate insieme', function () {
        $character = sheet();
        CharacterItem::factory()->for($character)->armor('Cotta di Maglia')->create();

        expect(fn () => CharacterItem::factory()->for($character)->armor('Armatura a Piastre')->create())
            ->toThrow(UniqueConstraintViolationException::class);
    });

    it('ma gli oggetti riposti non danno fastidio', function () {
        $character = sheet();

        CharacterItem::factory()->for($character)->count(5)->create();

        expect($character->items()->count())->toBe(5);
    });
});

describe('idoneità di un oggetto a uno slot', function () {
    it('accetta solo ciò che è a catalogo', function () {
        expect(EquipmentSlot::Armor->accepts('Cotta di Maglia'))->toBeTrue()
            ->and(EquipmentSlot::Armor->accepts('Spada Lunga'))->toBeFalse()
            ->and(EquipmentSlot::Weapon->accepts('Spada Lunga'))->toBeTrue()
            ->and(EquipmentSlot::Weapon->accepts('Cotta di Maglia'))->toBeFalse()
            ->and(EquipmentSlot::Shield->accepts('Scudo +1'))->toBeTrue()
            ->and(EquipmentSlot::Shield->accepts('Pugnale'))->toBeFalse();
    });

    it('gli oggetti magici non stanno in uno slot: ci si va in sintonia', function () {
        foreach (EquipmentSlot::cases() as $slot) {
            expect($slot->accepts('Anello di Protezione'))->toBeFalse();
        }
    });
});

describe('oggetti magici che alterano le caratteristiche', function () {
    it('cambiano i punteggi efficaci, non quelli base', function () {
        $character = sheet(['str' => 12]);
        CharacterItemEffect::factory()->for($character)
            ->setTo(Ability::Str, 21, 'Cintura di Forza del Gigante')->create();

        $character->load('items', 'itemEffects');

        expect($character->baseScores()->score(Ability::Str))->toBe(12)
            ->and($character->effectiveScores()->score(Ability::Str))->toBe(21);
    });

    it('si riflettono sulla CA se toccano la Destrezza', function () {
        $character = sheet(['dex' => 12]);
        CharacterItemEffect::factory()->for($character)
            ->bonus(Ability::Dex, 4, 'Mantello della Destrezza')->create();

        expect($character->load('items', 'itemEffects')->armorClass())->toBe(13);
    });

    it('muovono i PF massimi se toccano la Costituzione', function () {
        $character = sheet(['con' => 14, 'hp_max' => 40, 'level' => 5]);
        CharacterItemEffect::factory()->for($character)
            ->setTo(Ability::Con, 16, 'Amuleto della Salute')->create();

        $character->load('items', 'itemEffects');

        expect($character->effectiveHpMax())->toBe(45)
            ->and($character->hp_max)->toBe(40);
    });

    it('tolgono quel che avevano dato, quando l\'oggetto se ne va', function () {
        $character = sheet(['con' => 14, 'hp_max' => 40, 'level' => 5]);
        $effect = CharacterItemEffect::factory()->for($character)
            ->setTo(Ability::Con, 16)->create();

        expect($character->load('items', 'itemEffects')->effectiveHpMax())->toBe(45);

        $effect->delete();

        expect($character->fresh()->load('items', 'itemEffects')->effectiveHpMax())->toBe(40);
    });
});

describe('tiri e prove', function () {
    it('il tiro salvezza aggiunge la competenza dove c\'è', function () {
        $character = sheet([
            'con' => 16, 'cha' => 8, 'level' => 5,
            'saving_throws' => ['con' => true],
        ]);

        $character->load('items', 'itemEffects');

        expect($character->savingThrow(Ability::Con))->toBe(6)
            ->and($character->savingThrow(Ability::Cha))->toBe(-1);
    });

    it('la prova di abilità conta l\'Esperto due volte', function () {
        $character = sheet([
            'dex' => 18, 'level' => 5,
            'skills' => ['stealth' => 'expert', 'acrobatics' => 'proficient'],
        ]);

        $character->load('items', 'itemEffects');

        expect($character->skillBonus('stealth'))->toBe(10)
            ->and($character->skillBonus('acrobatics'))->toBe(7)
            ->and($character->skillBonus('sleightOfHand'))->toBe(4);
    });

    it('la CD degli incantesimi usa i punteggi efficaci', function () {
        $character = sheet(['class' => 'Mago', 'int' => 16, 'level' => 5]);
        CharacterItemEffect::factory()->for($character)
            ->bonus(Ability::Int, 2, 'Cerchietto dell\'Intelletto')->create();

        $character->load('items', 'itemEffects');

        expect($character->spellSaveDc())->toBe(15)
            ->and($character->spellAttackBonus())->toBe(7);
    });

    it('chi non lancia incantesimi non ha CD', function () {
        $character = sheet(['class' => 'Barbaro', 'spell_ability' => null]);

        expect($character->load('items', 'itemEffects')->spellSaveDc())->toBeNull();
    });
});

describe('cancellazione a cascata', function () {
    it('porta via inventario, armi, talenti, effetti e incantesimi', function () {
        $character = sheet();
        CharacterItem::factory()->for($character)->count(3)->create();
        CharacterItemEffect::factory()->for($character)->create();

        $character->delete();

        expect(CharacterItem::count())->toBe(0)
            ->and(CharacterItemEffect::count())->toBe(0);
    });
});
