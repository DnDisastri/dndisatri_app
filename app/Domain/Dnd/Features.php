<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\ActionCost;
use App\Enums\FeatureUsesFormula;
use App\Enums\RestType;
use App\Models\Character;
use App\Models\ClassFeature;
use Illuminate\Support\Collection;

/**
 * I privilegi di classe e sottoclasse già presi da un personaggio, per la scheda,
 * con i loro usi. In un multiclasse ogni classe conta fino al livello che ha in
 * quella classe. I dati stanno nella tabella `class_features`.
 */
final class Features
{
    /** Usi senza limite, come l'Ira al 20º livello. */
    public const UNLIMITED = -1;

    /**
     * @return Collection<int, array{
     *     id: int, origine: string, livello: int, nome: string, costo: ActionCost,
     *     usi: ?string, testo: string, mio: bool, controllare: bool, daTurno: bool,
     *     massimo: ?int, usati: int, ritorno: ?RestType, consuma: ?string, quanti: int,
     *     attivabile: bool, attivo: bool, effetto: ?string, livelloClasse: int, privilegio: ClassFeature
     * }>
     */
    public static function for(Character $character): Collection
    {
        $catalogo = app(FeatureCatalogue::class);
        $privilegi = collect();

        foreach ($character->classLevels() as $classe => $livello) {
            $privilegi = $privilegi->concat(
                self::raccogli($character, $catalogo->of(ClassFeature::CLASS_OWNER, $classe), $classe, $livello)
            );

            foreach (self::sottoclassiDi($character, $classe) as $sottoclasse) {
                $privilegi = $privilegi->concat(
                    self::raccogli($character, $catalogo->of(ClassFeature::SUBCLASS_OWNER, $sottoclasse), $sottoclasse, $livello)
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
                if (! app(FeatureCatalogue::class)->has(ClassFeature::SUBCLASS_OWNER, $sottoclasse)) {
                    $mancanti[] = $sottoclasse;
                }
            }
        }

        return $mancanti;
    }

    /**
     * I privilegi da turno raggruppati per costo, nell'ordine del turno. Quelli
     * fuori dal turno (sociali, d'esplorazione) vanno in Storia.
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

    /** I privilegi tenuti fuori dal turno: vanno in Storia. */
    public static function fuoriDalTurno(Character $character): Collection
    {
        return self::for($character)->reject(fn (array $p) => $p['daTurno'])->values();
    }

    /** I privilegi con un contatore o che si attivano: quelli del pannello Risorse. */
    public static function conUsi(Character $character): Collection
    {
        return self::for($character)
            ->filter(fn (array $p) => $p['massimo'] !== null || $p['attivabile'] || $p['consuma'] !== null)
            ->values();
    }

    /** Quanti usi ha, a quel livello nella classe; null se non si contano. */
    public static function massimo(ClassFeature $privilegio, Character $character, int $livelloClasse): ?int
    {
        $mod = fn () => $privilegio->uses_ability === null ? 0 : $character->effectiveScores()->modifier($privilegio->uses_ability);

        return match ($privilegio->uses_formula) {
            null => null,
            FeatureUsesFormula::Fixed => (int) $privilegio->uses_value,
            FeatureUsesFormula::Proficiency => $character->proficiencyBonus(),
            FeatureUsesFormula::HalfProficiency => (int) ceil($character->proficiencyBonus() / 2),
            FeatureUsesFormula::Ability => max(1, $mod()),
            FeatureUsesFormula::AbilityPlusOne => max(1, 1 + $mod()),
            FeatureUsesFormula::ClassLevel => $livelloClasse,
            FeatureUsesFormula::ClassLevelTimesFive => 5 * $livelloClasse,
            FeatureUsesFormula::Table => self::daTabella($privilegio->uses_table ?? [], $livelloClasse),
        };
    }

    /** @param list<array{level: int, count: int}> $tabella  0 usi vuol dire senza limite */
    private static function daTabella(array $tabella, int $livello): int
    {
        $usi = 0;

        foreach (collect($tabella)->sortBy(fn ($riga) => (int) $riga['level']) as $riga) {
            if ($livello >= (int) $riga['level']) {
                $usi = (int) $riga['count'] === 0 ? self::UNLIMITED : (int) $riga['count'];
            }
        }

        return $usi;
    }

    /** I privilegi fino a un livello, con l'origine (classe o sottoclasse) per i multiclasse. */
    private static function raccogli(Character $character, Collection $lista, string $origine, int $livello): Collection
    {
        $usati = $character->feature_uses ?? [];
        $attivi = $character->active_features ?? [];

        return $lista
            ->filter(fn (ClassFeature $p) => $p->level <= $livello)
            ->map(fn (ClassFeature $p) => [
                'id' => $p->getKey(),
                'origine' => $origine,
                'livello' => $p->level,
                'nome' => $p->name,
                'costo' => $p->cost,
                'usi' => $p->uses_text,
                'testo' => $p->text,
                // Nome tradotto da noi, non dal SRD italiano: la scheda lo segnala.
                'mio' => $p->our_name,
                // Riassunto non ancora ricontrollato sul manuale: la scheda avvisa.
                'controllare' => $p->to_check,
                'daTurno' => $p->on_turn,
                'massimo' => self::massimo($p, $character, $livello),
                'usati' => (int) ($usati[$p->getKey()] ?? 0),
                'ritorno' => $p->recharge,
                'consuma' => $p->spends,
                'quanti' => $p->spends_amount,
                'attivabile' => $p->can_activate,
                'attivo' => in_array($p->getKey(), $attivi, true),
                'effetto' => $p->active_text,
                'livelloClasse' => $livello,
                'privilegio' => $p,
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
