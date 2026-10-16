<?php

declare(strict_types=1);

namespace App\Enums;

/** I tipi di creatura del manuale. */
enum CreatureType: string
{
    case Aberration = 'aberration';
    case Beast = 'beast';
    case Celestial = 'celestial';
    case Construct = 'construct';
    case Dragon = 'dragon';
    case Elemental = 'elemental';
    case Fey = 'fey';
    case Fiend = 'fiend';
    case Giant = 'giant';
    case Humanoid = 'humanoid';
    case Monstrosity = 'monstrosity';
    case Ooze = 'ooze';
    case Plant = 'plant';
    case Undead = 'undead';

    public function label(): string
    {
        return match ($this) {
            self::Aberration => 'Aberrazione',
            self::Beast => 'Bestia',
            self::Celestial => 'Celestiale',
            self::Construct => 'Costrutto',
            self::Dragon => 'Drago',
            self::Elemental => 'Elementale',
            self::Fey => 'Folletto',
            self::Fiend => 'Immondo',
            self::Giant => 'Gigante',
            self::Humanoid => 'Umanoide',
            self::Monstrosity => 'Mostruosità',
            self::Ooze => 'Melma',
            self::Plant => 'Vegetale',
            self::Undead => 'Non morto',
        };
    }

    /** @return array<string, string> in ordine alfabetico italiano */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->sort()->all();
    }
}
