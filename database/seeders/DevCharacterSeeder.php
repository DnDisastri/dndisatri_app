<?php

namespace Database\Seeders;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Personaggi finti per lo sviluppo, scelti per coprire i casi che si vedono
 * nella scheda: un incantatore completo, uno che non lancia niente, un
 * warlock con gli slot da patto e un caduto per il memoriale.
 */
class DevCharacterSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DevCharacterSeeder crea dati finti: non va eseguito in produzione.');
        }

        $players = User::role(User::ROLE_PLAYER)->get();

        if ($players->isEmpty()) {
            $this->command?->warn('Nessun giocatore: lancia prima DevUserSeeder.');

            return;
        }

        $this->wizard($players->first());

        if ($players->count() > 1) {
            $this->barbarian($players->get(1));
        }
    }

    /** Mago di 5° livello: slot, CD incantesimi, oggetto magico che alza l'INT. */
    private function wizard(User $player): void
    {
        $character = Character::factory()->ownedBy($player)->create([
            'name' => 'Elandra di Valcupa',
            'class' => 'Mago',
            'subclass' => 'Evocazione',
            'race' => 'Elfo',
            'background' => 'Studioso',
            'level' => 5,
            'hit_die' => 6,
            'str' => 8, 'dex' => 16, 'con' => 14,
            'int' => 16, 'wis' => 12, 'cha' => 10,
            'hp_max' => 32, 'hp_current' => 27, 'hp_temp' => 0,
            'gp' => 240,
            'spell_ability' => 'int',
            'saving_throws' => ['int' => true, 'wis' => true],
            'skills' => ['arcana' => 'expert', 'history' => 'proficient', 'investigation' => 'proficient'],
            'spell_slots_used' => [1 => 2],
            'notes' => 'Cerca il fratello scomparso nelle rovine a nord.',
        ]);

        $character->items()->createMany([
            ['name' => 'Armatura di Cuoio', 'category' => 'Armature', 'qty' => 1, 'value' => 10],
            ['name' => 'Pugnale', 'category' => 'Armi', 'qty' => 1, 'value' => 2],
            ['name' => 'Pozione di Cura', 'category' => 'Pozioni', 'qty' => 3, 'value' => 50],
        ]);

        $character->items()->where('name', 'Armatura di Cuoio')
            ->update(['equipped_slot' => EquipmentSlot::Armor]);

        $character->weapons()->create([
            'name' => 'Pugnale', 'attack_ability' => Ability::Dex, 'weapon_bonus' => 0, 'damage' => '1d4+3',
        ]);

        $character->itemEffects()->create([
            'name' => "Cerchietto dell'Intelletto",
            'ability' => Ability::Int,
            'mode' => ItemEffectMode::Set,
            'value' => 19,
        ]);

        $character->spells()->createMany([
            ['name' => 'Dardo di Fuoco', 'level' => 0],
            ['name' => 'Mano Magica', 'level' => 0],
            ['name' => 'Dardo Incantato', 'level' => 1],
            ['name' => 'Scudo', 'level' => 1],
            ['name' => 'Immagine Speculare', 'level' => 2],
            ['name' => 'Palla di Fuoco', 'level' => 3],
        ]);

        $character->feats()->create([
            'name' => 'Iniziato alla Magia', 'level' => 4, 'source' => 'asi',
            'description' => 'Due trucchetti e un incantesimo di 1° livello da un\'altra lista.',
        ]);
    }

    /** Barbaro di 3°: nessun incantesimo, armatura pesante, tanti PF. */
    private function barbarian(User $player): void
    {
        $character = Character::factory()->ownedBy($player)->create([
            'name' => 'Grommash Spaccateschi',
            'class' => 'Barbaro',
            'subclass' => 'Cammino del Berserker',
            'race' => 'Mezzorco',
            'background' => 'Eroe Popolano',
            'level' => 3,
            'hit_die' => 12,
            'str' => 17, 'dex' => 14, 'con' => 16,
            'int' => 8, 'wis' => 10, 'cha' => 12,
            'hp_max' => 38, 'hp_current' => 38,
            'gp' => 55,
            'saving_throws' => ['str' => true, 'con' => true],
            'skills' => ['athletics' => 'proficient', 'intimidation' => 'proficient', 'survival' => 'proficient'],
        ]);

        $character->items()->createMany([
            ['name' => 'Cotta di Maglia', 'category' => 'Armature', 'qty' => 1, 'value' => 75],
            ['name' => 'Scudo', 'category' => 'Armature', 'qty' => 1, 'value' => 10],
            ['name' => 'Ascia Bipenne', 'category' => 'Armi', 'qty' => 1, 'value' => 30],
            ['name' => 'Corda di Canapa', 'category' => 'Equipaggiamento', 'qty' => 1, 'value' => 1],
        ]);

        $character->items()->where('name', 'Cotta di Maglia')->update(['equipped_slot' => EquipmentSlot::Armor]);
        $character->items()->where('name', 'Scudo')->update(['equipped_slot' => EquipmentSlot::Shield]);
        $character->items()->where('name', 'Ascia Bipenne')->update(['equipped_slot' => EquipmentSlot::Weapon]);

        $character->weapons()->create([
            'name' => 'Ascia Bipenne', 'attack_ability' => Ability::Str, 'weapon_bonus' => 0, 'damage' => '1d12+3',
        ]);
    }
}
