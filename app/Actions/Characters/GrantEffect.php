<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Models\Character;
use App\Models\CharacterItemEffect;
use App\Models\User;
use RuntimeException;

/**
 * Benedizioni e maledizioni: un effetto permanente che non sta su un oggetto.
 *
 * È il caso a parte che il gruppo ha voluto tenere. Tutto il resto degli
 * effetti segue l'oggetto in sintonia e sparisce vendendolo; questi no —
 * valgono sempre, e li toglie solo un DM.
 *
 * Non passano dalla bacheca, come gli altri strumenti diretti dei DM, ma
 * finiscono nel registro attività.
 */
final class GrantEffect
{
    public function grant(
        Character $character,
        User $actor,
        string $name,
        Ability $ability,
        ItemEffectMode $mode,
        int $value,
    ): CharacterItemEffect {
        $this->assertManages($actor);

        return $character->itemEffects()->create([
            // Nessun oggetto: è quello che distingue una benedizione dal bonus
            // di un anello.
            'character_item_id' => null,
            'name' => $name,
            'ability' => $ability->value,
            'mode' => $mode->value,
            'value' => $value,
        ]);
    }

    /**
     * Toglie un effetto.
     *
     * Vale per le benedizioni, che non hanno un oggetto da vendere, ma anche
     * per rimediare a un effetto assegnato per sbaglio.
     */
    public function revoke(CharacterItemEffect $effect, User $actor): void
    {
        $this->assertManages($actor);

        $effect->delete();
    }

    private function assertManages(User $actor): void
    {
        if (! $actor->isDm() && ! $actor->isAdmin()) {
            throw new RuntimeException('Solo un DM o un admin assegna e revoca effetti permanenti.');
        }
    }
}
