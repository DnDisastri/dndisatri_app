<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Il grado d'avventuriero, dedotto dal livello.
 *
 * Non è una statistica di gioco della 5e: è un fregio del gruppo, cinque fasce
 * con un medaglione di metallo diverso — dal legno del novizio al platino del
 * leggendario. Si **calcola sempre dal livello**, mai salvato, come la Classe
 * Armatura e l'iniziativa: sale da solo quando sale il livello.
 *
 * Le fasce non si sovrappongono: ogni livello cade in un grado solo.
 */
enum AdventurerRank: string
{
    case Novizio = 'novizio';
    case Apprendista = 'apprendista';
    case Professionista = 'professionista';
    case Maestro = 'maestro';
    case Leggendario = 'leggendario';

    public static function fromLevel(int $level): self
    {
        return match (true) {
            $level <= 2 => self::Novizio,
            $level <= 4 => self::Apprendista,
            $level <= 8 => self::Professionista,
            $level <= 12 => self::Maestro,
            default => self::Leggendario,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Novizio => 'Novizio',
            self::Apprendista => 'Apprendista',
            self::Professionista => 'Professionista',
            self::Maestro => 'Maestro',
            self::Leggendario => 'Leggendario',
        };
    }

    /** Il metallo del medaglione, in parola. */
    public function metal(): string
    {
        return match ($this) {
            self::Novizio => 'legno',
            self::Apprendista => 'bronzo',
            self::Professionista => 'argento',
            self::Maestro => 'oro',
            self::Leggendario => 'platino',
        };
    }

    /**
     * Il colore del metallo, per tingere il medaglione.
     *
     * Un esadecimale e non una classe Tailwind: sono cinque tinte di metallo
     * che il tema non ha, e servono uguali su chiaro e scuro.
     */
    public function color(): string
    {
        return match ($this) {
            self::Novizio => '#9c6b3f',       // legno
            self::Apprendista => '#cd7f32',   // bronzo
            self::Professionista => '#9ca3af', // argento
            self::Maestro => '#d4af37',       // oro
            self::Leggendario => '#b8c9d0',   // platino
        };
    }

    /** La fascia di livelli, per la legenda che la spiega. */
    public function range(): string
    {
        return match ($this) {
            self::Novizio => '1–2',
            self::Apprendista => '3–4',
            self::Professionista => '5–8',
            self::Maestro => '9–12',
            self::Leggendario => '13+',
        };
    }
}
