<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Models\GameClass;
use App\Models\Subclass;
use Illuminate\Support\Collection;

/**
 * Cosa concede una classe: dado vita, tiri salvezza, abilità, incantesimi.
 *
 * Tutto viene dalla tabella `game_classes`, che si cambia dal pannello.
 */
final class ClassRules
{
    private static function of(?string $class): ?GameClass
    {
        return app(ClassCatalogue::class)->find($class);
    }

    /** @return Collection<int,string> i nomi delle classi, nell'ordine del catalogo */
    public static function names(): Collection
    {
        return app(ClassCatalogue::class)->all()->keys()->values();
    }

    public static function exists(?string $class): bool
    {
        return self::of($class) !== null;
    }

    public static function hitDie(?string $class): int
    {
        return self::of($class)?->hit_die ?? 8;
    }

    /** @return list<string> le due caratteristiche con competenza nei TS */
    public static function savingThrows(?string $class): array
    {
        return self::of($class)?->saves ?? [];
    }

    /** Quante abilità sceglie la classe in creazione. */
    public static function skillCount(?string $class): int
    {
        return self::of($class)?->skill_count ?? 0;
    }

    /** @return list<string> fra quali abilità può scegliere */
    public static function skillChoices(?string $class): array
    {
        return array_values(self::of($class)?->skill_choices ?? []);
    }

    public static function proficiencies(?string $class): ?string
    {
        return self::of($class)?->proficiencies;
    }

    public static function equipmentSummary(?string $class): ?string
    {
        return self::of($class)?->equipment_summary;
    }

    /** @return array{fixed: list<array<string,mixed>>, choices: list<array<string,mixed>>} */
    public static function startingEquipment(?string $class): array
    {
        $equip = self::of($class)?->starting_equipment ?? [];

        // Le scelte si ricordano per indice: le chiavi che il pannello potrebbe lasciare non devono contare.
        return [
            'fixed' => array_values($equip['fixed'] ?? []),
            'choices' => collect($equip['choices'] ?? [])->map(fn (array $scelta) => [
                ...$scelta,
                'options' => collect($scelta['options'] ?? [])->map(fn (array $opzione) => [
                    ...$opzione,
                    'items' => array_values($opzione['items'] ?? []),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public static function casterType(?string $class): CasterType
    {
        return self::of($class)?->caster_type ?? CasterType::None;
    }

    public static function spellAbility(?string $class): ?Ability
    {
        return self::of($class)?->spell_ability;
    }

    public static function subclassLevel(?string $class): int
    {
        return self::of($class)?->subclass_level ?? 3;
    }

    /** @return list<string> */
    public static function subclasses(?string $class): array
    {
        return self::subclassesOf($class)->pluck('name')->all();
    }

    /**
     * Le sottoclassi con la loro descrizione, per chi deve farle scegliere.
     *
     * @return Collection<int,Subclass>
     */
    public static function subclassesOf(?string $class): Collection
    {
        return app(SubclassCatalogue::class)->of($class);
    }

    /**
     * Trucchetti e incantesimi noti al primo livello, o null se la classe non
     * lancia ancora. Per chi prepara, «prepared»: il numero dipende dai
     * punteggi e lo scioglie `spellCountAtFirst`.
     *
     * @return array{cantrips: int, spells: int|string}|null
     */
    public static function spellsKnownAtFirst(?string $class): ?array
    {
        $classe = self::of($class);

        if ($classe === null) {
            return null;
        }

        $preparaSubito = self::prepares($class, 1) && $classe->spells_at_1 === null;

        if ($classe->cantrips_at_1 === 0 && $classe->spells_at_1 === null && ! $preparaSubito) {
            return null;
        }

        return ['cantrips' => $classe->cantrips_at_1, 'spells' => $classe->spells_at_1 ?? 'prepared'];
    }

    /** Quanti incantesimi può scegliere, sciogliendo i «preparati». */
    public static function spellCountAtFirst(?string $class, AbilityScores $scores): int
    {
        $known = self::spellsKnownAtFirst($class);

        if ($known === null) {
            return 0;
        }

        if ($known['spells'] !== 'prepared') {
            return (int) $known['spells'];
        }

        $ability = self::spellAbility($class);

        return $ability === null ? 0 : self::preparationLimit($class, 1, $scores->modifier($ability));
    }

    /**
     * La classe prepara gli incantesimi ogni giorno invece di conoscerne un
     * numero fisso? Con il livello, dice anche se ci è già arrivata (il
     * Paladino prepara dal 2°).
     */
    public static function prepares(?string $class, ?int $level = null): bool
    {
        $classe = self::of($class);

        return $classe !== null && $classe->prepares && ($level === null || $level >= $classe->prepare_from_level);
    }

    /** Quanti ne prepara: caratteristica più livello (o metà), mai meno di uno. */
    public static function preparationLimit(?string $class, int $level, int $modifier): int
    {
        if (! self::prepares($class, $level)) {
            return 0;
        }

        return max(1, $modifier + (self::of($class)->prepare_half_level ? intdiv($level, 2) : $level));
    }

    /** Sostituisce un incantesimo conosciuto salendo di livello in questa classe? */
    public static function swapsSpells(?string $class): bool
    {
        return (bool) self::of($class)?->swaps_spells;
    }

    /** Copia incantesimi dalle pergamene nel suo libro? */
    public static function copiesScrolls(?string $class): bool
    {
        return (bool) self::of($class)?->copies_scrolls;
    }

    /** @return list<string> gli incantesimi che la classe può imparare */
    public static function spellList(?string $class): array
    {
        return array_values(self::of($class)?->spell_list ?? []);
    }

    /** Il livello di un incantesimo, dedotto dalla descrizione della libreria. */
    public static function spellLevel(string $name): int
    {
        $description = SpellName::description($name) ?? '';

        if (str_contains($description, 'Trucchetto')) {
            return 0;
        }

        preg_match('/liv\.\s*(\d)/', $description, $matches);

        return (int) ($matches[1] ?? 1);
    }
}
