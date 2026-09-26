<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use App\Models\CharacterItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La sintonia con un oggetto magico.
 *
 * Non è la stessa cosa di indossare: un'armatura si mette addosso e occupa uno
 * slot, un oggetto magico lo si sintonizza — e se ne tengono **tre**, come dice
 * il regolamento.
 *
 * È la sintonia a far valere gli effetti sui punteggi. Toglierla, o vendere
 * l'oggetto, li spegne: prima di questa azione un bonus approvato restava sui
 * punteggi per sempre.
 */
final class AttuneItem
{
    public function attune(CharacterItem $item): CharacterItem
    {
        return DB::transaction(function () use ($item) {
            $character = Character::whereKey($item->character_id)->lockForUpdate()->firstOrFail();

            if ($item->attuned) {
                return $item;
            }

            $inUse = CharacterItem::where('character_id', $character->getKey())
                ->where('attuned', true)
                ->count();

            if ($inUse >= Character::ATTUNEMENT_LIMIT) {
                throw new RuntimeException(
                    'Si tengono in sintonia al massimo '.Character::ATTUNEMENT_LIMIT
                    .' oggetti: togline uno prima di aggiungerne un altro.'
                );
            }

            // `attuned` non è mass-assignable di proposito: la sintonia è
            // un'azione, non un campo di modulo.
            $item->forceFill(['attuned' => true])->save();

            return $item;
        });
    }

    public function release(CharacterItem $item): CharacterItem
    {
        $item->forceFill(['attuned' => false])->save();

        return $item;
    }
}
