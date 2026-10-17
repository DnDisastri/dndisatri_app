<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

enum CasterType: string
{
    case None = 'none';
    case Full = 'full';
    case Half = 'half';
    /** Mezzo incantatore che arrotonda per eccesso, come l'Artefice: ha slot dal 1° livello. */
    case HalfUp = 'half_up';
    case Third = 'third';
    case Pact = 'pact';

    /**
     * Il tipo di incantatore dipende dalla classe, tranne per Guerriero e
     * Ladro: le sottoclassi Cavaliere Mistico e Furfante Arcano li rendono
     * incantatori di un terzo.
     */
    public static function for(?string $class, ?string $subclass = null): self
    {
        $type = ClassRules::casterType($class);

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

    public function label(): string
    {
        return match ($this) {
            self::None => 'Non lancia incantesimi',
            self::Full => 'Incantatore completo',
            self::Half => 'Mezzo incantatore',
            self::HalfUp => 'Mezzo incantatore, arrotondato per eccesso',
            self::Third => 'Un terzo di incantatore',
            self::Pact => 'Magia del patto',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }

    public function castsSpells(): bool
    {
        return $this !== self::None;
    }
}
