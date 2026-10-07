<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use RuntimeException;

/**
 * Consuma e recupera gli slot incantesimo, senza approvazione. La chiave è il
 * livello dello slot, o `pact` per la riserva del Warlock.
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
        // `pact` si misura sempre sulla riserva da patto: in un multiclasse `spellSlots()` sono i normali.
        if ($slot === 'pact') {
            return $character->pactSlots()->total();
        }

        return $character->spellSlots()->countAt((int) $slot);
    }
}
