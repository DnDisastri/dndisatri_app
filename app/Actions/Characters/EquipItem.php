<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Enums\EquipmentSlot;
use App\Models\CharacterItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Indossare e riporre l'equipaggiamento.
 *
 * Fino a ora l'equipaggiamento si assegnava una volta sola, alla creazione:
 * comprare un'armatura migliore non serviva a niente, perché restava nello
 * zaino per sempre e la Classe Armatura si calcola da ciò che si indossa.
 *
 * Due regole reggono tutto il resto:
 *
 * - **uno slot ospita un oggetto solo**, e lo garantisce un indice univoco sulla
 *   tabella. Qui lo slot si libera *prima* di occuparlo, o la scrittura viene
 *   rifiutata dal database;
 * - **si indossa un pezzo per volta**: da una pila di tre pugnali se ne impugna
 *   uno, e gli altri due restano nello zaino.
 */
final class EquipItem
{
    /**
     * Indossa un oggetto. Senza slot lo deduce dal catalogo: un'armatura va
     * indosso, uno scudo al braccio, un'arma in mano.
     *
     * Gli oggetti magici non passano di qui: non si indossano in uno slot, ci
     * si va in sintonia, e se ne tengono tre. Vedi `AttuneItem`.
     */
    public function equip(CharacterItem $item, ?EquipmentSlot $slot = null): CharacterItem
    {
        $slot ??= $this->naturalSlotFor($item->name)
            ?? throw new RuntimeException("«{$item->name}» non è qualcosa che si indossa.");

        if (! $slot->accepts($item->name)) {
            throw new RuntimeException("«{$item->name}» non va nello slot {$slot->label()}.");
        }

        return DB::transaction(function () use ($item, $slot) {
            $occupant = CharacterItem::where('character_id', $item->character_id)
                ->where('equipped_slot', $slot)
                ->whereKeyNot($item->getKey())
                ->first();

            if ($occupant !== null) {
                $this->unequip($occupant);
            }

            $target = $item->qty > 1 ? $this->splitOne($item) : $item;

            // `equipped_slot` non è mass-assignable di proposito: equipaggiare
            // è un'azione, non un campo di modulo.
            $target->forceFill(['equipped_slot' => $slot])->save();

            return $target;
        });
    }

    /** Ripone nello zaino. Su un oggetto già riposto non fa niente. */
    public function unequip(CharacterItem $item): CharacterItem
    {
        if (! $item->isEquipped()) {
            return $item;
        }

        return DB::transaction(function () use ($item) {
            // Se in zaino c'è già una pila dello stesso oggetto ci si
            // riaccorpa, invece di lasciare due righe identiche che poi
            // l'inventario mostrerebbe separate.
            $stack = CharacterItem::where('character_id', $item->character_id)
                ->where('name', $item->name)
                ->whereNull('equipped_slot')
                ->whereKeyNot($item->getKey())
                ->first();

            if ($stack === null) {
                $item->forceFill(['equipped_slot' => null])->save();

                return $item;
            }

            $stack->increment('qty', $item->qty);
            $item->delete();

            return $stack->refresh();
        });
    }

    /** Stacca un pezzo dalla pila e lo restituisce come riga a sé. */
    private function splitOne(CharacterItem $item): CharacterItem
    {
        $item->decrement('qty');

        return CharacterItem::create([
            'character_id' => $item->character_id,
            'name' => $item->name,
            'category' => $item->category,
            'qty' => 1,
            // Il valore va difeso: la colonna non ammette null, ma una riga
            // appena creata non ha ancora letto i valori predefiniti del
            // database, e da lì arriverebbe un null.
            'value' => $item->value ?? 0,
            'details' => $item->details,
        ]);
    }

    /** Lo slot naturale di un oggetto secondo i dati di gioco. */
    private function naturalSlotFor(string $name): ?EquipmentSlot
    {
        foreach ([EquipmentSlot::Armor, EquipmentSlot::Shield, EquipmentSlot::Weapon] as $slot) {
            if ($slot->accepts($name)) {
                return $slot;
            }
        }

        return null;
    }
}
