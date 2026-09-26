<?php

declare(strict_types=1);

namespace App\Enums;

enum RestType: string
{
    case Long = 'long';
    case Short = 'short';

    public function label(): string
    {
        return match ($this) {
            self::Long => 'Riposo lungo',
            self::Short => 'Riposo breve',
        };
    }

    /**
     * Cosa fa davvero, detto a chi preme.
     *
     * Il breve **non rimette a posto i punti ferita da solo**, e non è una
     * mancanza dell'applicazione: nel regolamento durante un riposo breve si
     * spendono i dadi vita, uno alla volta, e sono quelli a curare.
     */
    public function description(): string
    {
        return match ($this) {
            self::Long => 'Punti ferita al massimo, tutti gli slot, e metà dei dadi vita indietro.',
            self::Short => 'Tornano gli slot da patto. Per i punti ferita, spendi un dado vita.',
        };
    }
}
