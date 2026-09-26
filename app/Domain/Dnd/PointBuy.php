<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * L'acquisto dei punteggi in creazione.
 *
 * Ogni punteggio parte da 8 e si compra fino a 15, con un costo crescente: i
 * valori da 9 a 13 costano un punto ciascuno, il 14 e il 15 ne costano due.
 * Il budget è 27.
 *
 * I bonus di specie si sommano **dopo**, e non entrano nel conto: è per questo
 * che il tetto qui è 15 e non 20.
 */
final class PointBuy
{
    public const MIN_SCORE = 8;

    public const MAX_SCORE = 15;

    public static function budget(): int
    {
        return (int) config('dnd.character.point_buy_budget', 27);
    }

    /** Costo di un singolo punteggio, o null se fuori dai limiti. */
    public static function costOf(int $score): ?int
    {
        return config("dnd.character.point_buy_cost.{$score}");
    }

    /** @param array<string,int> $scores */
    public static function spent(array $scores): int
    {
        return collect($scores)->sum(fn (int $score) => self::costOf($score) ?? 0);
    }

    /** @param array<string,int> $scores */
    public static function remaining(array $scores): int
    {
        return self::budget() - self::spent($scores);
    }

    /**
     * I punteggi sono comprabili e stanno nel budget?
     *
     * @param  array<string,int>  $scores
     */
    public static function isValid(array $scores): bool
    {
        foreach (Ability::cases() as $ability) {
            $score = $scores[$ability->value] ?? null;

            if ($score === null || self::costOf($score) === null) {
                return false;
            }
        }

        return self::remaining($scores) >= 0;
    }

    /** @return array<string,int> tutti a 8, il punto di partenza */
    public static function starting(): array
    {
        return collect(Ability::cases())
            ->mapWithKeys(fn (Ability $a) => [$a->value => self::MIN_SCORE])
            ->all();
    }

    /**
     * Somma i bonus di specie ai punteggi comprati.
     *
     * L'Umano ha `all: 1`, cioè +1 ovunque. Umano Variante e Mezzelfo hanno
     * bonus da assegnare a scelta, che arrivano separati.
     *
     * @param  array<string,int>  $scores
     * @param  array<string,int>  $chosen  i +1 a scelta, per le specie che li hanno
     * @return array<string,int>
     */
    public static function withSpecies(array $scores, ?string $species, array $chosen = []): array
    {
        $bonuses = config("dnd.species.{$species}.asi", []);

        if (isset($bonuses['all'])) {
            $bonuses = collect(Ability::cases())
                ->mapWithKeys(fn (Ability $a) => [$a->value => $bonuses['all']])
                ->all();
        }

        foreach ([...$bonuses, ...$chosen] as $ability => $bonus) {
            if (isset($scores[$ability])) {
                $scores[$ability] += $bonus;
            }
        }

        return $scores;
    }

    /** Quanti +1 a scelta concede la specie (Umano Variante, Mezzelfo). */
    public static function freeBonusesFor(?string $species): int
    {
        return match ($species) {
            'Umano (Variante)', 'Mezzelfo' => 2,
            default => 0,
        };
    }
}
