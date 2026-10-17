<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\BackgroundRules;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\PointBuy;
use App\Domain\Dnd\SpeciesRules;
use App\Domain\Dnd\SpellSlots;
use App\Domain\Dnd\SubraceCatalogue;
use App\Enums\EquipmentSlot;
use App\Enums\RulesEdition;
use App\Models\Background;
use App\Models\Character;
use App\Models\Feat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Crea un personaggio dalle scelte della procedura guidata (§5.8 del brief).
 *
 * Decide da sé: livello 1, PF con il dado vita pieno più Costituzione, tiri
 * salvezza dalla classe, monete dal background, e il primo oggetto valido per
 * slot già indossato, così la CA è giusta da subito.
 */
final class CreateCharacter
{
    /**
     * @param  array<string,int>  $boughtScores  i punteggi comprati col point buy
     * @param  array<string,int>  $speciesChoices  i +1 a scelta, per le specie che li hanno
     * @param  list<string>  $skills  le abilità scelte fra quelle della classe
     * @param  list<string>  $spells  gli incantesimi scelti
     * @param  list<string>  $speciesSkills  le competenze a scelta di Umano Variante e Mezzelfo
     * @param  array<string,int>  $backgroundBonuses  regole 2024: +2/+1 o +1/+1/+1 alle caratteristiche del background
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
        ?string $subspecies = null,
        array $backgroundSkills = [],
        ?int $pack = null,
        ?string $privateStory = null,
        RulesEdition $edition = RulesEdition::Classic,
        array $speciesSkills = [],
        ?int $featId = null,
        array $backgroundBonuses = [],
    ): Character {
        if (! $edition->isCreatableBy($owner)) {
            throw new InvalidArgumentException("Le regole {$edition->label()} non sono ancora aperte ai giocatori.");
        }

        $this->validate($name, $class, $species, $background, $boughtScores, $skills, $spells, $edition);
        $this->validateBackgroundSkills($background, $backgroundSkills, $edition);
        $speciesSkills = $this->validateSpeciesSkills($species, $speciesSkills, $edition);

        $feat = SpeciesRules::grantsFeat($species, $edition) ? Feat::pick($edition, $featId) : null;

        if (SpeciesRules::grantsFeat($species, $edition) && $feat === null) {
            throw new InvalidArgumentException("La specie «{$species}» sceglie un talento al primo livello.");
        }

        // Nel 2024 il talento della specie è di origine, come quello del background.
        if ($feat !== null && $edition === RulesEdition::Revised && Feat::find($featId)?->category !== Feat::ORIGIN) {
            throw new InvalidArgumentException('Nelle regole 2024 la specie dà un talento di origine.');
        }

        $sfondo = BackgroundRules::of($background, $edition);
        [$bonusSfondo, $talentoSfondo] = $this->fromBackground($sfondo, $backgroundBonuses);

        $sottorazza = app(SubraceCatalogue::class)->find($species, $subspecies, $edition);

        $scores = PointBuy::withSpecies($boughtScores, $species, $speciesChoices, $subspecies, $edition);

        foreach ($bonusSfondo as $ability => $bonus) {
            $scores[$ability] += $bonus;
        }

        $abilityScores = AbilityScores::fromArray($scores);
        $hitDie = ClassRules::hitDie($class);

        return DB::transaction(function () use (
            $owner, $name, $class, $species, $background, $subclass, $story,
            $scores, $abilityScores, $hitDie, $skills, $spells, $equipmentChoices, $sottorazza,
            $backgroundSkills, $pack, $privateStory, $edition, $speciesSkills, $feat, $sfondo, $talentoSfondo
        ) {
            $character = Character::create([
                'user_id' => $owner->getKey(),
                'name' => $name,
                'class' => $class,
                'subclass' => $subclass,
                'race' => $species,
                'subrace' => $sottorazza?->name,
                'background' => $background,
                // Quello che gli altri leggeranno di lui. Alla creazione si
                // scrive liberamente: è dopo che le modifiche passano da un DM.
                'story' => $story,
                'private_story' => $privateStory,
                'level' => 1,
                'rules_edition' => $edition,
                'hit_die' => $hitDie,
                ...$scores,
                // L'elfo dei boschi cammina più svelto della sua razza: se la
                // sottorazza dichiara un passo, è il suo che vale.
                'speed' => $sottorazza?->speed ?? SpeciesRules::speed($species, $edition),
                // Al primo livello il dado vita si prende pieno.
                'hp_max' => max(1, $hitDie + $abilityScores->modifier(Ability::Con)),
                'hp_current' => max(1, $hitDie + $abilityScores->modifier(Ability::Con)),
                'gp' => $sfondo->gp,
                'saving_throws' => collect(ClassRules::savingThrows($class))
                    ->mapWithKeys(fn (string $ability) => [$ability => true])
                    ->all(),
                'skills' => $this->skillMap([...$skills, ...$speciesSkills], $sfondo->skills ?? [], $backgroundSkills),
                'spell_slots_used' => [],
                'spell_ability' => SpellSlots::abilityFor($class)?->value,
                // Razza e sottorazza di seguito: sulla scheda si deve leggere
                // da dove viene ogni bonus, non solo il totale nei punteggi.
                'species_traits' => collect([
                    SpeciesRules::traits($species, $edition),
                    $sottorazza?->traits,
                ])->filter()->join("\n\n"),
                'background_feature' => $sfondo->feature,
            ]);

            if ($feat !== null) {
                $character->feats()->create([
                    'name' => $feat['name'],
                    'description' => $feat['description'],
                    'level' => 1,
                    'source' => 'species',
                ]);
            }

            if ($talentoSfondo !== null) {
                $character->feats()->create([
                    'name' => $talentoSfondo->name,
                    'description' => $talentoSfondo->description,
                    'level' => 1,
                    'source' => 'background',
                ]);
            }

            $this->giveStartingItems($character, $class, $sfondo, $equipmentChoices, $pack);
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
    private function validate(string $name, string $class, string $species, string $background, array $scores, array $skills, array $spells, RulesEdition $edition): void
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

        if (! SpeciesRules::exists($species, $edition)) {
            throw new InvalidArgumentException("Specie sconosciuta: {$species}.");
        }

        if (! BackgroundRules::exists($background, $edition)) {
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

    /** Quante abilità lascia scegliere il background: zero per quelli del manuale. */
    public static function freeSkillsFor(?string $background, RulesEdition $edition = RulesEdition::Classic): int
    {
        return BackgroundRules::freeSkills($background, $edition);
    }

    /** Lo zaino scelto, o il primo dell'elenco se il background ne prevede uno. */
    public static function pack(?string $background, ?int $index, RulesEdition $edition = RulesEdition::Classic): ?array
    {
        if (self::freeSkillsFor($background, $edition) === 0) {
            return null;
        }

        $zaini = config('dnd.backgrounds.packs', []);

        return $zaini[$index] ?? $zaini[0] ?? null;
    }

    /**
     * @param  list<string>  $chosen
     * @return list<string>
     */
    private function validateSpeciesSkills(string $species, array $chosen, RulesEdition $edition): array
    {
        $quante = SpeciesRules::skillChoices($species, $edition);
        $chosen = array_values(array_unique(array_filter($chosen)));

        if (count($chosen) !== $quante) {
            throw new InvalidArgumentException($quante === 0
                ? "La specie «{$species}» non fa scegliere abilità."
                : "La specie «{$species}» vuole {$quante} abilità a scelta, diverse.");
        }

        $esistenti = array_keys(config('dnd.character.skills', []));

        foreach ($chosen as $skill) {
            if (! in_array($skill, $esistenti, true)) {
                throw new InvalidArgumentException("Abilità sconosciuta: {$skill}.");
            }
        }

        return $chosen;
    }

    /** @param  list<string>  $chosen */
    private function validateBackgroundSkills(string $background, array $chosen, RulesEdition $edition): void
    {
        $quante = self::freeSkillsFor($background, $edition);

        if ($quante === 0) {
            return;
        }

        $chosen = array_values(array_unique($chosen));

        if (count($chosen) !== $quante) {
            throw new InvalidArgumentException("Il background «{$background}» vuole {$quante} abilità a scelta.");
        }

        $esistenti = array_keys(config('dnd.character.skills', []));

        foreach ($chosen as $skill) {
            if (! in_array($skill, $esistenti, true)) {
                throw new InvalidArgumentException("Abilità sconosciuta: {$skill}.");
            }
        }
    }

    /**
     * Regole 2024: i bonus alle caratteristiche e il talento di origine
     * arrivano dal background. Nel 2014 non ce ne sono.
     *
     * @param  array<string,int>  $bonuses
     * @return array{0: array<string,int>, 1: ?Feat}
     */
    private function fromBackground(Background $sfondo, array $bonuses): array
    {
        if ($sfondo->rules_edition !== RulesEdition::Revised) {
            if (array_filter($bonuses) !== []) {
                throw new InvalidArgumentException('Nelle regole 2014 il background non aumenta le caratteristiche.');
            }

            return [[], null];
        }

        $talento = filled($sfondo->origin_feat)
            ? Feat::ofEdition(RulesEdition::Revised)->where('name', $sfondo->origin_feat)->first()
            : null;

        return [BackgroundRules::validateBonuses($sfondo, $bonuses), $talento];
    }

    /** @param  list<string>  $dalBackground */
    private function skillMap(array $chosen, array $dalBackground, array $backgroundSkills = []): array
    {
        // «Personalizzato» non ne impone nessuna: le sue sono quelle che il
        // giocatore ha scelto, e valgono come se fossero fisse.
        $fromBackground = [...$dalBackground, ...$backgroundSkills];

        return collect([...$chosen, ...$fromBackground])
            ->unique()
            ->mapWithKeys(fn (string $skill) => [$skill => 'proficient'])
            ->all();
    }

    /**
     * L'equipaggiamento iniziale: fisso di classe, scelte A/B (senza scelta,
     * la prima) e kit del background.
     *
     * @param  array<int,int>  $choices  indice della scelta => indice dell'opzione
     */
    private function giveStartingItems(Character $character, string $class, Background $sfondo, array $choices, ?int $pack = null): void
    {
        $equipment = ClassRules::startingEquipment($class);

        $items = $equipment['fixed'] ?? [];

        foreach ($equipment['choices'] ?? [] as $index => $choice) {
            $picked = $choice['options'][$choices[$index] ?? 0] ?? $choice['options'][0] ?? null;

            $items = [...$items, ...($picked['items'] ?? [])];
        }

        $items = [...$items, ...($sfondo->starting_items ?? [])];

        // Lo zaino del background personalizzato: un oggetto solo, che porta
        // il suo contenuto scritto nella descrizione.
        if ($scelto = self::pack($sfondo->name, $pack, $sfondo->rules_edition)) {
            $items[] = ['name' => $scelto['name'], 'qty' => 1, 'category' => 'Varie'];
        }

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
                ->first(fn ($item) => $slot->accepts($item->catalogKey()));

            // Da una pila di due pugnali se ne impugna uno: lo stacca EquipItem.
            if ($item !== null) {
                app(EquipItem::class)->equip($item, $slot);
            }
        }
    }
}
