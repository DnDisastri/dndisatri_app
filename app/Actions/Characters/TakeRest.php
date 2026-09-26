<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Enums\RestType;
use App\Models\Character;

/**
 * I riposi (§5.6 del brief).
 *
 * - **lungo**: azzera tutti gli slot consumati e rimette i punti ferita al
 *   massimo, azzerando i temporanei;
 * - **breve**: azzera solo gli slot da patto, che sono la particolarità del
 *   Warlock — pochi, ma tornano molto più spesso. Non tocca i punti ferita.
 *
 * Il recupero dei punti ferita è la decisione D8. La vecchia applicazione non
 * lo faceva, ma non era una scelta del gruppo: era una dimenticanza, e nel
 * regolamento il riposo lungo rimette a nuovo.
 */
final class TakeRest
{
    public function handle(Character $character, RestType $type): Character
    {
        $used = $character->spell_slots_used ?? [];

        $remaining = match ($type) {
            RestType::Long => [],
            // Del riposo breve si salva tutto tranne il patto.
            RestType::Short => collect($used)->except('pact')->all(),
        };

        $restored = match ($type) {
            // Il massimo **efficace**: se un oggetto magico alza la
            // Costituzione, il riposo porta al valore che vale adesso.
            //
            // I dadi vita tornano a **metà per volta**, come dice il
            // regolamento: una notte non ti rimette in mano tutta la riserva,
            // ed è il motivo per cui due giornate dure di fila si sentono.
            RestType::Long => [
                'hp_current' => $character->effectiveHpMax(),
                'hp_temp' => 0,
                'hit_dice_used' => max(0, (int) $character->hit_dice_used - self::recuperoDadi($character)),
            ],
            RestType::Short => [],
        };

        $character->forceFill([
            'spell_slots_used' => $remaining,
            ...$restored,
        ])->save();

        return $character;
    }

    /** Metà dei dadi vita totali, arrotondata per difetto, mai meno di uno. */
    private static function recuperoDadi(Character $character): int
    {
        return max(1, intdiv($character->hitDiceTotal(), 2));
    }
}
