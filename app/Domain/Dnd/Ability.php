<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

/**
 * Le sei caratteristiche. Le chiavi tecniche restano in inglese (`str`, `dex`),
 * le etichette mostrate ai giocatori vengono da config/dnd/character.php così
 * che esista una sola fonte per i nomi italiani.
 */
enum Ability: string
{
    case Str = 'str';
    case Dex = 'dex';
    case Con = 'con';
    case Int = 'int';
    case Wis = 'wis';
    case Cha = 'cha';

    /** Sigla di tre lettere: FOR, DES, COS, INT, SAG, CAR. */
    public function label(): string
    {
        return config("dnd.character.stat_labels.{$this->value}", strtoupper($this->value));
    }

    /** Nome esteso: Forza, Destrezza, ... */
    public function fullName(): string
    {
        return config("dnd.character.save_names.{$this->value}", $this->value);
    }

    /**
     * Modificatore di caratteristica: floor((punteggio - 10) / 2).
     *
     * intdiv() non va bene: tronca verso zero, mentre qui serve arrotondare
     * verso il basso anche per i punteggi sotto 10 (punteggio 7 → -2, non -1).
     */
    public static function modifierFor(int $score): int
    {
        return (int) floor(($score - 10) / 2);
    }

    /** Formatta un modificatore col segno esplicito: +3, -1, +0. */
    public static function format(int $modifier): string
    {
        return ($modifier >= 0 ? '+' : '').$modifier;
    }
}
