<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

enum CasterType: string
{
    case None = 'none';
    case Full = 'full';
    case Half = 'half';
    case Third = 'third';
    case Pact = 'pact';

    /**
     * Il tipo di incantatore dipende dalla classe, tranne per Guerriero e
     * Ladro: le sottoclassi Cavaliere Mistico e Furfante Arcano li rendono
     * incantatori di un terzo.
     */
    public static function for(?string $class, ?string $subclass = null): self
    {
        $caster = config("dnd.classes.list.{$class}.caster");

        if ($caster === null) {
            return self::None;
        }

        $type = self::from($caster);

        // Cavaliere Mistico e Furfante Arcano lanciano pur stando in una
        // classe che non lancia, e una homebrew può fare lo stesso: il
        // contrassegno sta sulla sottoclasse, non in un elenco a parte.
        if ($type === self::None && $subclass !== null) {
            $lancia = ClassRules::subclassesOf($class)
                ->firstWhere('name', $subclass)?->third_caster ?? false;

            if ($lancia) {
                return self::Third;
            }
        }

        return $type;
    }

    public function castsSpells(): bool
    {
        return $this !== self::None;
    }
}
