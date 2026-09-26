<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use Illuminate\Support\Collection;

/**
 * Le regole del multiclasse.
 *
 * Sono tre, e la prima è quella che sorprende sempre.
 *
 * **Gli slot non si sommano.** Un Chierico 3 / Paladino 2 non ha gli slot di un
 * chierico di 3° più quelli di un paladino di 2°: si calcola un *livello da
 * incantatore combinato* — i livelli da incantatore completo per intero, quelli
 * da mezzo divisi per due, quelli da un terzo divisi per tre, tutti arrotondati
 * per difetto — e si legge la tabella dell'incantatore completo. Quel
 * personaggio ha quindi gli slot di un incantatore di 4°: dei suoi due livelli
 * da Paladino ne conta uno.
 *
 * **Gli slot da patto restano fuori.** Il Warlock non entra nel conto e i suoi
 * slot vivono a parte: un Warlock 2 / Mago 3 ha *due* riserve distinte, che si
 * recuperano con riposi diversi.
 *
 * **I tiri salvezza non si accumulano.** Arrivano solo dalla prima classe, ed è
 * la ragione per cui nel gioco conta quale si è presa per prima.
 */
final class Multiclass
{
    /**
     * Il livello da incantatore combinato.
     *
     * @param  array<string,int>  $levels  classe => livello in quella classe
     */
    public static function casterLevel(array $levels): int
    {
        $total = 0;

        foreach ($levels as $class => $level) {
            $total += match (CasterType::for($class)) {
                CasterType::Full => $level,
                CasterType::Half => intdiv($level, 2),
                CasterType::Third => intdiv($level, 3),
                // Il patto non entra nel conto: ha una riserva sua.
                CasterType::Pact, CasterType::None => 0,
            };
        }

        return $total;
    }

    /**
     * Gli slot normali di un multiclasse, patto escluso.
     *
     * Con una classe sola si torna alla tabella di quella classe: un
     * personaggio non multiclassato non deve passare da un calcolo diverso solo
     * perché il calcolo esiste.
     *
     * @param  array<string,int>  $levels
     */
    public static function slots(array $levels): SpellSlotSet
    {
        $casting = array_filter(
            $levels,
            fn (string $class) => CasterType::for($class) !== CasterType::None,
            ARRAY_FILTER_USE_KEY,
        );

        if ($casting === []) {
            return SpellSlotSet::none();
        }

        if (count($casting) === 1) {
            $class = array_key_first($casting);

            return SpellSlots::for(CasterType::for($class), $casting[$class]);
        }

        $level = self::casterLevel($levels);

        return $level < 1
            ? SpellSlotSet::none()
            : SpellSlots::for(CasterType::Full, $level);
    }

    /**
     * Gli slot da patto, che vivono a parte.
     *
     * @param  array<string,int>  $levels
     */
    public static function pactSlots(array $levels): SpellSlotSet
    {
        foreach ($levels as $class => $level) {
            if (CasterType::for($class) === CasterType::Pact) {
                return SpellSlots::for(CasterType::Pact, $level);
            }
        }

        return SpellSlotSet::none();
    }

    /**
     * I punteggi bastano per entrare in `$entering` avendo già `$current`?
     *
     * I requisiti valgono **in entrambe le direzioni**: per prendere Paladino
     * bisogna soddisfare quelli del Paladino e quelli di ogni classe già
     * posseduta. Chi non ci arriva, nel manuale, non multiclassa affatto.
     *
     * Qui la regola avvisa e non impedisce: la richiesta si può fare lo stesso,
     * e un DM può approvarla comunque. Vedi `unmetRequirements()`.
     *
     * @param  list<string>  $current  le classi già possedute
     */
    public static function isAllowed(AbilityScores $scores, array $current, string $entering): bool
    {
        return self::unmetRequirements($scores, $current, $entering) === [];
    }

    /**
     * Quali requisiti mancano, in italiano, per poterlo dire a chi decide.
     *
     * @param  list<string>  $current
     * @return list<string>
     */
    public static function unmetRequirements(AbilityScores $scores, array $current, string $entering): array
    {
        $minimum = (int) config('dnd.multiclass.minimum_score', 13);
        $missing = [];

        foreach ([...$current, $entering] as $class) {
            foreach (config("dnd.multiclass.prerequisites.{$class}", []) as $group) {
                $satisfied = collect($group)->contains(
                    fn (string $ability) => $scores->score(Ability::from($ability)) >= $minimum
                );

                if ($satisfied) {
                    continue;
                }

                $names = collect($group)
                    ->map(fn (string $a) => Ability::from($a)->fullName())
                    ->join(' o ');

                $missing[] = "{$class} richiede {$minimum} in {$names}";
            }
        }

        return array_values(array_unique($missing));
    }

    /**
     * Le abilità che si possono scegliere entrando in una classe come seconda.
     *
     * Quasi tutte le classi non ne danno nessuna. I tiri salvezza **mai**.
     *
     * @return array{count: int, from: list<string>}
     */
    public static function skillsOnEntry(string $class): array
    {
        $entry = config("dnd.multiclass.skills_on_entry.{$class}");

        if ($entry === null) {
            return ['count' => 0, 'from' => []];
        }

        $from = $entry['from'] === 'any'
            ? array_keys(config('dnd.character.skills', []))
            : $entry['from'];

        return ['count' => (int) $entry['count'], 'from' => array_values($from)];
    }

    /**
     * Il dado vita di ogni livello preso, per ricostruire i punti ferita.
     *
     * @param  array<string,int>  $levels
     * @return Collection<int,int>
     */
    public static function hitDice(array $levels): Collection
    {
        return collect($levels)->mapWithKeys(
            fn (int $level, string $class) => [$class => ClassRules::hitDie($class)]
        );
    }
}
