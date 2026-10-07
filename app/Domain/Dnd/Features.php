<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\ActionCost;
use App\Models\Character;
use Illuminate\Support\Collection;

/**
 * I privilegi di classe e sottoclasse già presi da un personaggio, per la scheda.
 * In un multiclasse ogni classe conta fino al livello che ha in quella classe.
 * I dati stanno in config/dnd/features.php, con le attribuzioni di licenza.
 */
final class Features
{
    /**
     * @return Collection<int, array{
     *     origine: string, livello: int, nome: string, costo: ActionCost,
     *     usi: ?string, testo: string, mio: bool, controllare: bool, daTurno: bool
     * }>
     */
    public static function for(Character $character): Collection
    {
        $privilegi = collect();

        foreach ($character->classLevels() as $classe => $livello) {
            $privilegi = $privilegi->concat(
                self::raccogli(config("dnd.features.classi.{$classe}", []), $classe, $livello)
            );

            foreach (self::sottoclassiDi($character, $classe) as $sottoclasse) {
                $privilegi = $privilegi->concat(
                    self::raccogli(config("dnd.features.sottoclassi.{$sottoclasse}", []), $sottoclasse, $livello)
                );
            }
        }

        return $privilegi->sortBy('livello')->values();
    }

    /**
     * Le sottoclassi senza privilegi scritti: la scheda lo dice invece di tacere.
     *
     * @return list<string>
     */
    public static function sottoclassiSenzaPrivilegi(Character $character): array
    {
        $mancanti = [];

        foreach ($character->classLevels() as $classe => $livello) {
            foreach (self::sottoclassiDi($character, $classe) as $sottoclasse) {
                if (config("dnd.features.sottoclassi.{$sottoclasse}") === null) {
                    $mancanti[] = $sottoclasse;
                }
            }
        }

        return $mancanti;
    }

    /**
     * I privilegi da turno raggruppati per costo, nell'ordine del turno. Quelli
     * marcati `da_turno: false` (sociali, d'esplorazione) vanno in Storia.
     */
    public static function perCosto(Character $character): Collection
    {
        $privilegi = self::for($character)
            ->filter(fn (array $p) => $p['daTurno'])
            ->groupBy(fn (array $p) => $p['costo']->value);

        return collect(ActionCost::ordered())
            ->mapWithKeys(fn (ActionCost $costo) => [$costo->value => $privilegi->get($costo->value, collect())])
            ->reject(fn (Collection $gruppo) => $gruppo->isEmpty());
    }

    /** I privilegi tenuti fuori dal turno (`da_turno: false`): vanno in Storia. */
    public static function fuoriDalTurno(Character $character): Collection
    {
        return self::for($character)->reject(fn (array $p) => $p['daTurno'])->values();
    }

    /** I privilegi fino a un livello, con l'origine (classe o sottoclasse) per i multiclasse. */
    private static function raccogli(array $lista, string $origine, int $livello): Collection
    {
        return collect($lista)
            ->filter(fn (array $p) => $p['livello'] <= $livello)
            ->map(fn (array $p) => [
                'origine' => $origine,
                'livello' => $p['livello'],
                'nome' => $p['nome'],
                'costo' => ActionCost::from($p['costo']),
                'usi' => $p['usi'] ?? null,
                'testo' => $p['testo'],
                // Nome tradotto da noi, non dal SRD italiano: la scheda lo segnala.
                'mio' => (bool) ($p['nome_mio'] ?? false),
                // Riassunto non ancora ricontrollato sul manuale: la scheda avvisa.
                'controllare' => (bool) ($p['da_controllare'] ?? false),
                'daTurno' => (bool) ($p['da_turno'] ?? true),
            ])
            ->values();
    }

    /**
     * La sottoclasse presa in una classe: da `character_classes`, o dalla
     * colonna della scheda come in `classLevels()`.
     *
     * @return list<string>
     */
    private static function sottoclassiDi(Character $character, string $classe): array
    {
        $righe = $character->relationLoaded('classes') ? $character->classes : $character->classes()->get();

        $dalle = $righe
            ->where('class', $classe)
            ->pluck('subclass')
            ->filter()
            ->all();

        if ($dalle !== []) {
            return array_values($dalle);
        }

        return $character->class === $classe && $character->subclass
            ? [$character->subclass]
            : [];
    }
}
