<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use RuntimeException;

/**
 * Consuma e recupera gli slot incantesimo.
 *
 * Non passa dalla bacheca: è lo stato di una serata, non una modifica alla
 * scheda. Nella vecchia applicazione era così, ed è giusto — far approvare a
 * un DM ogni Dardo Incantato sarebbe insostenibile.
 *
 * La chiave è il livello dello slot, o `pact` per il Warlock, che ha un solo
 * gruppo di slot tutti dello stesso livello.
 */
final class SpendSpellSlot
{
    public function spend(Character $character, int|string $slot): Character
    {
        $available = $this->availableAt($character, $slot);
        $used = $character->spell_slots_used ?? [];
        $spent = (int) ($used[$slot] ?? 0);

        if ($spent >= $available) {
            throw new RuntimeException('Non hai più slot di questo livello.');
        }

        $used[$slot] = $spent + 1;

        $character->forceFill(['spell_slots_used' => $used])->save();

        return $character;
    }

    /** Rimette a posto uno slot segnato per sbaglio. */
    public function recover(Character $character, int|string $slot): Character
    {
        $used = $character->spell_slots_used ?? [];
        $spent = (int) ($used[$slot] ?? 0);

        if ($spent <= 1) {
            unset($used[$slot]);
        } else {
            $used[$slot] = $spent - 1;
        }

        $character->forceFill(['spell_slots_used' => $used])->save();

        return $character;
    }

    private function availableAt(Character $character, int|string $slot): int
    {
        /*
         * La chiave `pact` si misura sulla riserva da patto, sempre — anche su
         * un Warlock multiclasse, dove `spellSlots()` sono i normali e il patto
         * vive a parte. Prima si guardava solo `spellSlots()`: uno Stregone 2 /
         * Warlock 3 che provava a spendere uno slot da patto si sentiva dire
         * «non hai più slot», perché nei suoi normali la chiave `pact` non c'è.
         */
        if ($slot === 'pact') {
            return $character->pactSlots()->total();
        }

        return $character->spellSlots()->countAt((int) $slot);
    }
}
