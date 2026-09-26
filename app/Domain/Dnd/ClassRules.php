<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use Illuminate\Support\Collection;

/**
 * Cosa concede una classe in creazione: dado vita, tiri salvezza competenti,
 * abilità scegliibili e incantesimi noti al primo livello.
 *
 * Tutto viene da config/dnd/, che è la traduzione 1:1 dei dati della vecchia
 * applicazione.
 */
final class ClassRules
{
    /** @return Collection<int,string> i nomi delle dodici classi */
    public static function names(): Collection
    {
        return collect(array_keys(config('dnd.classes.list', [])));
    }

    public static function exists(?string $class): bool
    {
        return $class !== null && config("dnd.classes.list.{$class}") !== null;
    }

    public static function hitDie(?string $class): int
    {
        return (int) config("dnd.classes.list.{$class}.hitDie", 8);
    }

    /** @return list<string> le due caratteristiche con competenza nei TS */
    public static function savingThrows(?string $class): array
    {
        return config("dnd.classes.list.{$class}.saves", []);
    }

    /** Quante abilità sceglie la classe in creazione. */
    public static function skillCount(?string $class): int
    {
        return (int) config("dnd.classes.list.{$class}.skills.count", 0);
    }

    /**
     * Fra quali abilità può scegliere.
     *
     * Il Bardo ha `'any'`: sceglie fra tutte e diciotto.
     *
     * @return list<string>
     */
    public static function skillChoices(?string $class): array
    {
        $from = config("dnd.classes.list.{$class}.skills.from", []);

        return $from === 'any' ? array_keys(config('dnd.character.skills', [])) : $from;
    }

    /** @return list<string> */
    public static function subclasses(?string $class): array
    {
        return collect(config("dnd.subclasses.{$class}", []))->pluck('name')->all();
    }

    /**
     * Trucchetti e incantesimi noti al primo livello, o null se la classe non
     * lancia ancora. Per i preparati il numero è `mod(caratteristica) + livello`,
     * quindi si calcola solo quando i punteggi sono noti.
     *
     * @return array{cantrips: int, spells: int|string}|null
     */
    public static function spellsKnownAtFirst(?string $class): ?array
    {
        return config("dnd.spells.known_at_level_1.{$class}");
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

        $ability = SpellSlots::abilityFor($class);

        // Preparati: modificatore più livello, mai meno di uno.
        return $ability === null
            ? 0
            : max(1, $scores->modifier($ability) + 1);
    }

    /**
     * La classe prepara gli incantesimi ogni giorno invece di conoscerne un
     * numero fisso? Nei dati di gioco sono Chierico e Druido.
     */
    public static function prepares(?string $class): bool
    {
        return (self::spellsKnownAtFirst($class)['spells'] ?? null) === 'prepared';
    }

    /** @return list<string> gli incantesimi che la classe può imparare */
    public static function spellList(?string $class): array
    {
        return config("dnd.spells.by_class.{$class}", []);
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
