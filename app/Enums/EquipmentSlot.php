<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Gli slot di equipaggiamento. Uno solo per slot, garantito dall'indice
 * univoco su `character_items`.
 *
 * Gli oggetti magici **non stanno qui**: non si indossano in un posto preciso,
 * ci si va in sintonia, e se ne tengono tre. Vedi `attuned` sull'inventario.
 */
enum EquipmentSlot: string
{
    case Weapon = 'weapon';
    case Armor = 'armor';
    case Shield = 'shield';

    public function label(): string
    {
        return match ($this) {
            self::Weapon => 'Arma',
            self::Armor => 'Armatura',
            self::Shield => 'Scudo',
        };
    }

    public function equipVerb(): string
    {
        return $this === self::Armor ? 'Indossa' : 'Impugna';
    }

    public function equippedLabel(): string
    {
        return $this === self::Armor ? 'indossata' : 'in mano';
    }

    public function emptyLabel(): string
    {
        return match ($this) {
            self::Weapon => 'nessuna arma',
            self::Armor => 'nessuna armatura',
            self::Shield => 'nessuno scudo',
        };
    }

    /** La chiave è il nome a catalogo, o la `base` di un oggetto con un nome suo. */
    public function accepts(string $catalogKey): bool
    {
        return config("dnd.combat.{$this->catalogSection()}.{$catalogKey}") !== null;
    }

    public static function naturalFor(string $catalogKey): ?self
    {
        foreach ([self::Armor, self::Shield, self::Weapon] as $slot) {
            if ($slot->accepts($catalogKey)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Le basi che si possono assegnare, per gruppo. Fuori «Arma +1» e «Scudo +1»:
     * il +N si scrive a parte, in `magic_bonus`.
     *
     * @return array<string, array<string, string>>
     */
    public static function bases(): array
    {
        $gruppi = [];

        foreach ([self::Armor, self::Shield, self::Weapon] as $slot) {
            $nomi = array_filter(
                array_keys(config("dnd.combat.{$slot->catalogSection()}", [])),
                fn (string $nome) => ! str_contains($nome, '+'),
            );

            $gruppi[$slot->groupLabel()] = array_combine($nomi, $nomi);
        }

        return $gruppi;
    }

    public static function isBase(?string $nome): bool
    {
        return $nome !== null && collect(self::bases())->contains(fn (array $gruppo) => isset($gruppo[$nome]));
    }

    public function groupLabel(): string
    {
        return match ($this) {
            self::Weapon => 'Armi',
            self::Armor => 'Armature',
            self::Shield => 'Scudi',
        };
    }

    private function catalogSection(): string
    {
        return match ($this) {
            self::Weapon => 'weapons',
            self::Armor => 'armor',
            self::Shield => 'shields',
        };
    }
}
