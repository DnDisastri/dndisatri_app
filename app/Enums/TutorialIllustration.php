<?php

declare(strict_types=1);

namespace App\Enums;

/** Illustrazioni fisse per un passo; il valore nomina il blocco in <x-tutorial-illustrazione>. */
enum TutorialIllustration: string
{
    case BottomBar = 'bottom-bar';
    case Menu = 'menu';
    case Hero = 'hero';
    case Sheet = 'sheet';
    case Quest = 'quest';
    case Market = 'market';
    case Closing = 'closing';

    public function label(): string
    {
        return match ($this) {
            self::BottomBar => 'La barra in basso',
            self::Menu => 'Il menù in alto',
            self::Hero => 'Creazione eroe',
            self::Sheet => 'La scheda del personaggio',
            self::Quest => "Card dell'incarico",
            self::Market => 'Le linguette del mercato',
            self::Closing => 'Chiusura (cerchio eroe)',
        };
    }

    /** Opzioni per il Select del pannello: valore => etichetta. */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases()),
            'label',
            'value',
        );
    }
}
