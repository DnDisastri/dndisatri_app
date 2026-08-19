<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Le quattro tabelle degli slot incantesimo, da config/dnd/spells.php.
 */
final class SpellSlots
{
    public static function for(CasterType $type, int $level): SpellSlotSet
    {
        if ($type === CasterType::None) {
            return SpellSlotSet::none();
        }

        if ($type === CasterType::Pact) {
            $entry = config("dnd.spells.slots.pact.{$level}");

            if ($entry === null) {
                return SpellSlotSet::none();
            }

            [$count, $spellLevel] = $entry;

            return SpellSlotSet::pact($count, $spellLevel);
        }

        // Le tabelle sono liste posizionali: l'indice 0 sono gli slot di
        // livello 1. I livelli in cui la classe non ha ancora slot mancano
        // del tutto dalla tabella (un Paladino non ne ha al livello 1).
        $row = config("dnd.spells.slots.{$type->value}.{$level}");

        if ($row === null) {
            return SpellSlotSet::none();
        }

        $slots = [];

        foreach ($row as $index => $count) {
            if ($count > 0) {
                $slots[$index + 1] = $count;
            }
        }

        return SpellSlotSet::standard($slots);
    }

    /** La caratteristica da incantatore della classe, se ne ha una. */
    public static function abilityFor(?string $class): ?Ability
    {
        $ability = config("dnd.spells.ability_by_class.{$class}");

        return $ability === null ? null : Ability::from($ability);
    }
}
