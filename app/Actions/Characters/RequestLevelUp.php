<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\HitPoints;
use App\Domain\Dnd\Multiclass;
use App\Domain\Dnd\Progression;
use App\Domain\Dnd\SpellSlots;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use InvalidArgumentException;

/**
 * Prepara la richiesta di passaggio di livello.
 *
 * Qui vive la regola più delicata del dominio, e l'ordine delle operazioni è
 * quello che conta:
 *
 * 1. l'ASI si applica ai punteggi **prima** di calcolare i PF;
 * 2. il guadagno del livello usa quindi il modificatore di Costituzione
 *    **nuovo**;
 * 3. se quel modificatore è salito, i livelli già acquisiti valgono un PF in
 *    più ciascuno.
 *
 * Il calcolo si fa adesso, non all'approvazione: il DM deve vedere i numeri
 * che sta approvando. Il risultato finisce nel diff, e l'approvazione si
 * limita ad applicarlo.
 */
final class RequestLevelUp
{
    /**
     * @param  string|null  $class  in quale classe salire; null significa la principale
     * @param  list<string>  $asiAbilities  una caratteristica per il +2, due per il +1/+1
     * @param  list<string>  $spells  gli incantesimi imparati salendo
     * @param  list<string>  $skills  le abilità scelte entrando in una classe nuova
     */
    public function handle(
        Character $character,
        User $requester,
        ?string $class = null,
        ?string $asiMode = null,
        array $asiAbilities = [],
        ?string $featName = null,
        ?string $featDescription = null,
        ?string $subclass = null,
        array $spells = [],
        array $skills = [],
    ): PendingChange {
        $newLevel = $character->level + 1;

        if ($newLevel > Progression::MAX_LEVEL) {
            throw new InvalidArgumentException('Il ventesimo livello è il tetto.');
        }

        $levels = $character->classLevels();
        $class ??= $character->class;

        $classUp = $this->prepareClassUp($character, $levels, $class, $skills);

        $scores = $character->baseScores()->toArray();
        $conBefore = Ability::modifierFor($scores['con']);

        $summary = ["Livello {$character->level} → {$newLevel}."];
        $summary[] = $classUp['is_new']
            ? "Nuova classe: {$class} 1."
            : "{$class} {$levels[$class]} → {$classUp['level']}.";

        $feat = null;

        // Gli aumenti restano legati al livello TOTALE (D18): è una deviazione
        // voluta dal manuale, dove dipendono dal livello nella singola classe.
        if (Progression::isAsiLevel($newLevel) && $asiMode !== null) {
            [$scores, $note, $feat] = $this->applyAsi(
                $scores, $asiMode, $asiAbilities, $featName, $featDescription
            );
            $summary[] = $note;
        }

        $conAfter = Ability::modifierFor($scores['con']);

        // Il dado vita è quello della classe in cui si sale, non quello della
        // scheda: un Guerriero che prende un livello da Mago tira un d6.
        $hitDie = ClassRules::hitDie($class);

        $gain = HitPoints::gainForLevel($hitDie, $conAfter);
        $retro = HitPoints::retroactiveGain($conAfter - $conBefore, $newLevel);

        $summary[] = "+{$gain} PF (d{$hitDie})";

        if ($retro > 0) {
            $summary[] = "+{$retro} PF retroattivi (COS +".($conAfter - $conBefore).')';
        }

        $diff = [
            'level' => $newLevel,
            'hp_max' => $character->hp_max + $gain + $retro,
            'hp_current' => $character->hp_current + $gain + $retro,
            ...$scores,
        ];

        if ($subclass !== null && ($classUp['subclass'] ?? null) === null) {
            $this->assertSubclassAllowed($class, $subclass, $classUp['level']);

            $classUp['subclass'] = $subclass;
            $summary[] = "Sottoclasse: {$subclass}";

            // La copia sulla scheda vale solo per la classe principale: è
            // quella che la Gilda mostra.
            if ($class === $character->class) {
                $diff['subclass'] = $subclass;
            }
        }

        $diff['class_up'] = $classUp;

        if ($classUp['unmet'] !== []) {
            // Non blocca (D15): chi approva vede cosa manca e decide se
            // concedere l'eccezione.
            $summary[] = '⚠ Requisiti non soddisfatti: '.implode('; ', $classUp['unmet']).'.';
        }

        if ($feat !== null) {
            // I talenti sono righe a parte, non colonne: l'approvazione le
            // riconosce da questa chiave, che il filtro dei campi scarta.
            $diff['feat'] = $feat;
        }

        if ($spells !== []) {
            $this->assertSpellsAllowed($character, $spells, $newLevel);

            // Stessa strada dei talenti: righe a parte sotto una chiave che il
            // filtro dei campi non riconosce come colonna.
            $diff['spells'] = array_values(array_unique($spells));
            $summary[] = 'Impara: '.implode(', ', $diff['spells']).'.';
        }

        return PendingChange::create([
            'character_id' => $character->getKey(),
            'requested_by' => $requester->getKey(),
            'type' => PendingChangeType::LevelUp,
            'diff' => $diff,
            'summary' => implode(' ', $summary),
            'base_updated_at' => $character->updated_at,
        ]);
    }

    /**
     * @param  array<string,int>  $scores
     * @param  list<string>  $abilities
     * @return array{0: array<string,int>, 1: string, 2: array{name: string, description: ?string}|null}
     */
    private function applyAsi(
        array $scores,
        string $mode,
        array $abilities,
        ?string $featName,
        ?string $featDescription,
    ): array {
        $cap = Progression::ASI_SCORE_CAP;

        return match ($mode) {
            'plus2' => (function () use ($scores, $abilities, $cap) {
                $ability = $this->ability($abilities[0] ?? null);
                $scores[$ability->value] = min($cap, $scores[$ability->value] + 2);

                return [$scores, "ASI: +2 {$ability->label()}.", null];
            })(),

            'plus1' => (function () use ($scores, $abilities, $cap) {
                [$first, $second] = [$this->ability($abilities[0] ?? null), $this->ability($abilities[1] ?? null)];

                if ($first === $second) {
                    throw new InvalidArgumentException('Il +1/+1 va su due caratteristiche diverse.');
                }

                $scores[$first->value] = min($cap, $scores[$first->value] + 1);
                $scores[$second->value] = min($cap, $scores[$second->value] + 1);

                return [$scores, "ASI: +1 {$first->label()}, +1 {$second->label()}.", null];
            })(),

            'feat' => (function () use ($scores, $featName, $featDescription) {
                if (blank($featName)) {
                    throw new InvalidArgumentException('Serve il nome del talento.');
                }

                return [$scores, "Talento: {$featName}.", [
                    'name' => $featName,
                    'description' => $featDescription,
                ]];
            })(),

            default => throw new InvalidArgumentException("Scelta ASI sconosciuta: {$mode}."),
        };
    }

    private function ability(?string $value): Ability
    {
        return Ability::tryFrom((string) $value)
            ?? throw new InvalidArgumentException('Caratteristica non valida per l\'ASI.');
    }

    /**
     * Gli incantesimi imparati salendo di livello.
     *
     * **Non c'è un tetto al numero**, ed è deliberato: i dati di gioco dicono
     * quanti se ne conoscono solo al primo livello, e per i successivi non
     * esiste una tabella. Inventarla significherebbe riprogettare dati che il
     * brief chiede di riportare così come sono. Il numero lo controlla chi
     * approva, che ha il manuale davanti.
     *
     * Quello che si può controllare si controlla: la classe, i doppioni e il
     * livello raggiungibile.
     *
     * @param  list<string>  $spells
     */
    private function assertSpellsAllowed(Character $character, array $spells, int $newLevel): void
    {
        $available = ClassRules::spellList($character->class);
        $known = $character->spells()->pluck('name')->all();
        $reach = SpellSlots::for($character->casterType(), $newLevel)->maxSpellLevel();

        foreach (array_unique($spells) as $spell) {
            if (! in_array($spell, $available, true)) {
                throw new InvalidArgumentException(
                    "«{$spell}» non è nella lista di un {$character->class}."
                );
            }

            if (in_array($spell, $known, true)) {
                throw new InvalidArgumentException("«{$spell}» lo conosce già.");
            }

            $level = ClassRules::spellLevel($spell);

            // I trucchetti non consumano slot: si imparano sempre.
            if ($level > 0 && $level > $reach) {
                throw new InvalidArgumentException(
                    "«{$spell}» è di livello {$level}: al livello {$newLevel} non ci arriva ancora."
                );
            }
        }
    }

    /**
     * Il salto di livello in una classe: quale, a che livello arriva, se è
     * nuova, e cosa manca per averla.
     *
     * @param  array<string,int>  $levels
     * @param  list<string>  $skills
     * @return array{class: string, level: int, is_new: bool, subclass: ?string, skills: list<string>, unmet: list<string>}
     */
    private function prepareClassUp(Character $character, array $levels, string $class, array $skills): array
    {
        if (! ClassRules::exists($class)) {
            throw new InvalidArgumentException("Classe sconosciuta: {$class}.");
        }

        $isNew = ! array_key_exists($class, $levels);
        $unmet = [];

        if ($isNew) {
            if (count($levels) >= Character::MAX_CLASSES) {
                throw new InvalidArgumentException(
                    'Un personaggio non tiene più di '.Character::MAX_CLASSES.' classi.'
                );
            }

            // I requisiti si leggono sui punteggi di adesso: l'aumento di
            // questo passaggio, se c'è, arriva dopo. In pratica non capita
            // mai — gli aumenti non cadono al primo livello di una classe.
            $unmet = Multiclass::unmetRequirements(
                $character->baseScores(), array_keys($levels), $class
            );

            $this->assertEntrySkills($class, $skills);
        } elseif ($skills !== []) {
            throw new InvalidArgumentException(
                'Le abilità si scelgono solo entrando in una classe nuova.'
            );
        }

        $existing = $character->classes()->where('class', $class)->first();

        return [
            'class' => $class,
            'level' => ($levels[$class] ?? 0) + 1,
            'is_new' => $isNew,
            'subclass' => $existing?->subclass,
            'skills' => array_values(array_unique($skills)),
            'unmet' => $unmet,
        ];
    }

    /** @param list<string> $skills */
    private function assertEntrySkills(string $class, array $skills): void
    {
        $entry = Multiclass::skillsOnEntry($class);

        if (count($skills) > $entry['count']) {
            throw new InvalidArgumentException(
                $entry['count'] === 0
                    ? "Entrando in {$class} non si scelgono abilità."
                    : "Entrando in {$class} si sceglie {$entry['count']} abilità."
            );
        }

        foreach ($skills as $skill) {
            if (! in_array($skill, $entry['from'], true)) {
                throw new InvalidArgumentException(
                    "Entrando in {$class} non si può scegliere questa abilità."
                );
            }
        }
    }

    private function assertSubclassAllowed(string $class, string $subclass, int $classLevel): void
    {
        // Il livello che conta è quello **in quella classe**: un Mago sceglie
        // la scuola al 2° da Mago, non al secondo livello complessivo.
        $required = Progression::subclassLevel($class);

        if ($classLevel < $required) {
            throw new InvalidArgumentException(
                "Un {$class} sceglie la sottoclasse al livello {$required} della classe."
            );
        }

        $allowed = ClassRules::subclasses($class);

        if (! in_array($subclass, $allowed, true)) {
            throw new InvalidArgumentException(
                "«{$subclass}» non è una sottoclasse da {$class}."
            );
        }
    }
}
