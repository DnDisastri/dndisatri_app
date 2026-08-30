<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use InvalidArgumentException;

/**
 * Danni e cure durante la serata (decisione D7).
 *
 * Li segna il giocatore da solo, come gli slot incantesimo: sono lo stato di
 * una serata, non una modifica alla scheda. Prima di questa azione l'unica
 * strada era una proposta da far approvare a un DM — per ogni colpo preso.
 *
 * Le variazioni restano comunque nel registro attività, quindi la traccia non
 * si perde: quello che salta è l'approvazione, non la memoria.
 */
final class AdjustHitPoints
{
    /**
     * Danni subiti.
     *
     * I punti ferita temporanei fanno da scudo e si consumano per primi, com'è
     * il loro scopo. Quello che resta va sui punti ferita veri, che **possono
     * scendere sotto zero**: è una scelta del gruppo, e il numero negativo
     * racconta quanto male è andata.
     */
    public function damage(Character $character, int $amount): Character
    {
        $this->assertNotNegative($amount);

        $temp = (int) $character->hp_temp;
        $absorbed = min($temp, $amount);

        return $this->write(
            $character,
            current: $character->hp_current - ($amount - $absorbed),
            temp: $temp - $absorbed,
        );
    }

    /**
     * Cure ricevute. Non si supera il massimo **efficace**, cioè quello che
     * tiene già conto degli oggetti magici indossati.
     *
     * L'aritmetica è quella nuda: chi è a -5 e riceve 3 arriva a -2. Nel
     * regolamento una cura da sotto zero riporterebbe esattamente al valore
     * curato, ma qui il gruppo tiene i negativi apposta per vedere quanto in
     * profondità si è andati, e sommare è l'unica cosa che non perde quel dato.
     */
    public function heal(Character $character, int $amount): Character
    {
        $this->assertNotNegative($amount);

        return $this->write(
            $character,
            current: min($character->hp_current + $amount, $character->effectiveHpMax()),
            temp: (int) $character->hp_temp,
        );
    }

    /**
     * Punti ferita temporanei da un incantesimo o da un oggetto.
     *
     * Non si sommano a quelli che ci sono già: vince il valore più alto, come
     * dice il regolamento.
     */
    public function grantTemporary(Character $character, int $amount): Character
    {
        $this->assertNotNegative($amount);

        return $this->write(
            $character,
            current: $character->hp_current,
            temp: max((int) $character->hp_temp, $amount),
        );
    }

    private function write(Character $character, int $current, int $temp): Character
    {
        // Non sono mass-assignable per il resto del sistema: qui si scrivono
        // di proposito, ed è l'unico posto che lo fa senza approvazione.
        $dati = [
            'hp_current' => $current,
            'hp_temp' => max(0, $temp),
        ];

        // Tornato sopra zero, non è più morente: i tiri contro morte si
        // azzerano da soli. È l'unico posto che li tocca oltre a chi li segna,
        // e sta qui perché è qui che si scopre di essere vivi.
        if ($current > 0) {
            $dati['death_save_successes'] = 0;
            $dati['death_save_failures'] = 0;
        }

        $character->forceFill($dati)->save();

        return $character;
    }

    private function assertNotNegative(int $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                'La quantità non può essere negativa: per la direzione opposta c\'è l\'altro metodo.'
            );
        }
    }
}
