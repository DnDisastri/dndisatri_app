<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\PointBuy;
use App\Domain\Dnd\SpellSlots;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Crea un personaggio dalle scelte della procedura guidata.
 *
 * Il flusso è quello collaudato in due anni d'uso (§5.8 del brief) e non va
 * riprogettato: classe, specie, point buy, background, abilità,
 * equipaggiamento, incantesimi.
 *
 * Alcune cose non sono scegliibili e vengono da qui:
 *
 * - il livello è **sempre 1**;
 * - i punti ferita al primo livello sono il dado vita **pieno** più il
 *   modificatore di Costituzione, non la media;
 * - i tiri salvezza competenti li decide la classe;
 * - l'oro iniziale lo decide il background;
 * - il primo oggetto valido per ogni slot viene **indossato da solo**, così la
 *   classe armatura è giusta fin dalla prima apertura della scheda.
 */
final class CreateCharacter
{
    /**
     * @param  array<string,int>  $boughtScores  i punteggi comprati col point buy
     * @param  array<string,int>  $speciesChoices  i +1 a scelta, per le specie che li hanno
     * @param  list<string>  $skills  le abilità scelte fra quelle della classe
     * @param  list<string>  $spells  gli incantesimi scelti
     */
    public function handle(
        User $owner,
        string $name,
        string $class,
        string $species,
        string $background,
        array $boughtScores,
        array $speciesChoices = [],
        array $skills = [],
        array $spells = [],
        array $equipmentChoices = [],
        ?string $subclass = null,
        ?string $story = null,
    ): Character {
        $this->validate($name, $class, $species, $background, $boughtScores, $skills, $spells);

        $scores = PointBuy::withSpecies($boughtScores, $species, $speciesChoices);
        $abilityScores = AbilityScores::fromArray($scores);
        $hitDie = ClassRules::hitDie($class);

        return DB::transaction(function () use (
            $owner, $name, $class, $species, $background, $subclass, $story,
            $scores, $abilityScores, $hitDie, $skills, $spells, $equipmentChoices
        ) {
            $character = Character::create([
                'user_id' => $owner->getKey(),
                'name' => $name,
                'class' => $class,
                'subclass' => $subclass,
                'race' => $species,
                'background' => $background,
                // Quello che gli altri leggeranno di lui. Alla creazione si
                // scrive liberamente: è dopo che le modifiche passano da un DM.
                'story' => $story,
                'level' => 1,
                'hit_die' => $hitDie,
                ...$scores,
                'speed' => config("dnd.species.{$species}.speed", 9),
                // Al primo livello il dado vita si prende pieno.
                'hp_max' => max(1, $hitDie + $abilityScores->modifier(Ability::Con)),
                'hp_current' => max(1, $hitDie + $abilityScores->modifier(Ability::Con)),
                'gp' => (int) config("dnd.backgrounds.list.{$background}.gp", 0),
                'saving_throws' => collect(ClassRules::savingThrows($class))
                    ->mapWithKeys(fn (string $ability) => [$ability => true])
                    ->all(),
                'skills' => $this->skillMap($skills, $background),
                'spell_slots_used' => [],
                'spell_ability' => SpellSlots::abilityFor($class)?->value,
                'species_traits' => config("dnd.species.{$species}.traits"),
                'background_feature' => config("dnd.backgrounds.features.{$background}"),
            ]);

            $this->giveStartingItems($character, $class, $background, $equipmentChoices);
            $this->giveSpells($character, $spells);
            $this->autoEquip($character);

            return $character;
        });
    }

    /**
     * @param  array<string,int>  $scores
     * @param  list<string>  $skills
     * @param  list<string>  $spells
     */
    private function validate(string $name, string $class, string $species, string $background, array $scores, array $skills, array $spells = []): void
    {
        // Un personaggio senza nome non deve nascere. Nel wizard il primo passo
        // lo pretende, ma partendo da una build si atterra dritti sul riepilogo
        // e quel controllo verrebbe scavalcato: qui è la difesa.
        if (blank(trim($name))) {
            throw new InvalidArgumentException('Il personaggio deve avere un nome.');
        }

        if (! ClassRules::exists($class)) {
            throw new InvalidArgumentException("Classe sconosciuta: {$class}.");
        }

        if (config("dnd.species.{$species}") === null) {
            throw new InvalidArgumentException("Specie sconosciuta: {$species}.");
        }

        if (config("dnd.backgrounds.list.{$background}") === null) {
            throw new InvalidArgumentException("Background sconosciuto: {$background}.");
        }

        if (! PointBuy::isValid($scores)) {
            throw new InvalidArgumentException('I punteggi non rispettano il point buy.');
        }

        $allowed = ClassRules::skillChoices($class);
        $count = ClassRules::skillCount($class);

        if (count($skills) !== $count) {
            throw new InvalidArgumentException("Devi scegliere esattamente {$count} abilità.");
        }

        if (count($skills) !== count(array_unique($skills))) {
            throw new InvalidArgumentException('Hai scelto due volte la stessa abilità.');
        }

        foreach ($skills as $skill) {
            if (! in_array($skill, $allowed, true)) {
                throw new InvalidArgumentException("Un {$class} non può scegliere questa abilità.");
            }
        }

        // Gli incantesimi: solo quelli della classe, e solo fino al 1º livello.
        // Si crea sempre a livello 1, dove gli unici slot sono di 1º: conoscere
        // un incantesimo di livello più alto significherebbe uno slot che non
        // c'è. Il wizard già non li mostra; qui è la difesa, perché quello che
        // arriva dal browser non ci si fida.
        $spellList = ClassRules::spellList($class);

        foreach ($spells as $spell) {
            if (! in_array($spell, $spellList, true)) {
                throw new InvalidArgumentException("Un {$class} non può imparare «{$spell}».");
            }

            if (ClassRules::spellLevel($spell) > 1) {
                throw new InvalidArgumentException("Al primo livello non si conoscono incantesimi oltre il 1º: «{$spell}».");
            }
        }
    }

    /**
     * Le abilità della classe più le due del background.
     *
     * @param  list<string>  $chosen
     * @return array<string,string>
     */
    private function skillMap(array $chosen, string $background): array
    {
        $fromBackground = config("dnd.backgrounds.list.{$background}.skills", []);

        return collect([...$chosen, ...$fromBackground])
            ->unique()
            ->mapWithKeys(fn (string $skill) => [$skill => 'proficient'])
            ->all();
    }

    /**
     * L'equipaggiamento iniziale: quello fisso della classe, le opzioni
     * scelte fra le alternative A/B, e il kit del background.
     *
     * Se una scelta non viene fatta si prende la prima opzione, che è quella
     * «standard» dei manuali.
     *
     * @param  array<int,int>  $choices  indice della scelta => indice dell'opzione
     */
    private function giveStartingItems(Character $character, string $class, string $background, array $choices): void
    {
        $equipment = config("dnd.equipment.{$class}", ['fixed' => [], 'choices' => []]);

        $items = $equipment['fixed'] ?? [];

        foreach ($equipment['choices'] ?? [] as $index => $choice) {
            $picked = $choice['options'][$choices[$index] ?? 0] ?? $choice['options'][0] ?? null;

            $items = [...$items, ...($picked['items'] ?? [])];
        }

        $items = [...$items, ...config("dnd.starting_items.by_background.{$background}", [])];

        foreach ($items as $item) {
            $character->addToInventory(
                name: $item['name'],
                qty: (int) ($item['qty'] ?? 1),
                category: $item['category'] ?? null,
            );
        }
    }

    /** @param list<string> $spells */
    private function giveSpells(Character $character, array $spells): void
    {
        foreach (array_unique($spells) as $spell) {
            $character->spells()->create([
                'name' => $spell,
                'level' => ClassRules::spellLevel($spell),
            ]);
        }
    }

    /**
     * Indossa il primo oggetto idoneo per ogni slot.
     *
     * Senza, un personaggio appena creato mostrerebbe la classe armatura di
     * chi gira disarmato, pur avendo l'armatura nello zaino.
     */
    private function autoEquip(Character $character): void
    {
        foreach ([EquipmentSlot::Armor, EquipmentSlot::Shield, EquipmentSlot::Weapon] as $slot) {
            $item = $character->items()
                ->whereNull('equipped_slot')
                ->get()
                ->first(fn ($item) => $slot->accepts($item->name));

            // `equipped_slot` non è mass-assignable di proposito: equipaggiare
            // è un'azione, non un campo di form.
            $item?->forceFill(['equipped_slot' => $slot])->save();
        }
    }
}
