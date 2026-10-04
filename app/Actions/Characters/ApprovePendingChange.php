<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Actions\Market\Purse;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Coins;
use App\Enums\EquipmentSlot;
use App\Enums\LedgerAction;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Exceptions\MarketException;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\RequestDecided;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Applica una richiesta approvata al personaggio.
 *
 * Il diff arriva da un giocatore: si applicano solo i campi ammessi per quel
 * tipo, o una richiesta costruita a mano potrebbe riscrivere `user_id`, `gp` o
 * `died_at`. Il bottino si somma al saldo riletto sotto blocco, per non
 * annullare le spese fatte mentre la richiesta aspettava.
 */
final class ApprovePendingChange
{
    /** Fuori di proposito: `gp`, `level`, `user_id` e `died_at` hanno strade loro. */
    private const EDITABLE = [
        'name', 'class', 'subclass', 'race', 'background',
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'speed', 'hp_max', 'hp_current', 'hp_temp',
        'saving_throws', 'skills', 'spell_ability',
        'species_traits', 'class_features', 'subclass_features', 'background_feature', 'notes',
        'story', 'photo_path',
    ];

    private const LEVEL_UP = [
        'level', 'hit_die', 'subclass',
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'hp_max', 'hp_current',
    ];

    /**
     * @param  array<int, array{base?: ?string, magic_bonus?: int|string|null}>  $itemFixes  le correzioni del DM agli oggetti del bottino, per indice
     */
    public function handle(PendingChange $change, User $reviewer, ?string $note = null, array $itemFixes = []): PendingChange
    {
        return DB::transaction(function () use ($change, $reviewer, $note, $itemFixes) {
            $locked = PendingChange::whereKey($change->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new RuntimeException('Questa richiesta è già stata decisa.');
            }

            if ($itemFixes !== [] && $locked->type === PendingChangeType::Loot) {
                $locked->grant_items = $this->fixItems($locked->grant_items ?? [], $itemFixes);
            }

            $character = Character::whereKey($locked->character_id)->lockForUpdate()->firstOrFail();

            // Prima di applicare: dopo la scheda ha già i valori nuovi.
            $locked->forceFill(['before' => $locked->snapshotOf($character)]);

            $delta = match ($locked->type) {
                PendingChangeType::CharacterEdit => $this->applyEdit($character, $locked),
                PendingChangeType::LevelUp => $this->applyLevelUp($character, $locked),
                PendingChangeType::Loot => $this->applyLoot($character, $locked),
                PendingChangeType::ItemEffect => $this->applyItemEffect($character, $locked),
            };

            $locked->forceFill([
                'status' => PendingChangeStatus::Approved,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $message = $locked->summary ?: $locked->type->label().' approvata';

            $character->refresh()->recordInLedger(
                LedgerAction::Approve,
                $locked->note ? "{$message} ({$locked->note})" : $message,
                $delta,
                $reviewer,
            );

            $locked->requestedBy()->first()?->notify(new RequestDecided($locked));

            return $locked;
        });
    }

    private function applyEdit(Character $character, PendingChange $change): Coins
    {
        $fields = $this->allowed($change->diff, self::EDITABLE);

        // La foto è un file sul disco privato: si pubblica solo ora, approvata.
        if ($pending = ($fields['photo_path'] ?? null)) {
            $published = app(CharacterPhoto::class)->publish($character, $pending);

            if ($published === null) {
                unset($fields['photo_path']);
            } else {
                $fields['photo_path'] = $published;
            }
        }

        $character->forceFill($fields)->save();

        return Coins::none();
    }

    private function applyLevelUp(Character $character, PendingChange $change): Coins
    {
        $character->forceFill($this->allowed($change->diff, self::LEVEL_UP))->save();

        // Talento, classe e incantesimi sono righe a parte: il filtro dei campi li scarta.
        if ($classUp = ($change->diff['class_up'] ?? null)) {
            $this->applyClassUp($character, $classUp);
        }

        if ($feat = ($change->diff['feat'] ?? null)) {
            $character->feats()->create([
                'name' => $feat['name'],
                'description' => $feat['description'] ?? null,
                'level' => $change->diff['level'] ?? $character->level,
                'source' => 'asi',
            ]);
        }

        // `firstOrCreate`: un incantesimo già conosciuto non si sdoppia.
        foreach ($change->diff['spells'] ?? [] as $spell) {
            $character->spells()->firstOrCreate(
                ['name' => $spell],
                ['level' => ClassRules::spellLevel($spell)],
            );
        }

        return Coins::none();
    }

    /**
     * La copia sulla scheda (`characters.level`, `class`, `subclass`) la scrive
     * solo questo metodo, nella stessa transazione delle righe, o si scolla.
     *
     * @param  array<string,mixed>  $classUp
     */
    private function applyClassUp(Character $character, array $classUp): void
    {
        $row = $character->classes()->updateOrCreate(
            ['class' => $classUp['class']],
            [
                'level' => (int) $classUp['level'],
                // Solo la prima classe dà i tiri salvezza competenti.
                'is_primary' => $character->classes()->count() === 0,
            ],
        );

        if (($classUp['subclass'] ?? null) !== null) {
            $row->forceFill(['subclass' => $classUp['subclass']])->save();
        }

        // Le abilità della nuova classe si aggiungono: una competenza non si perde.
        if ($skills = ($classUp['skills'] ?? [])) {
            $current = $character->skills ?? [];

            foreach ($skills as $skill) {
                $current[$skill] ??= 'proficient';
            }

            $character->forceFill(['skills' => $current])->save();
        }
    }

    private function applyLoot(Character $character, PendingChange $change): Coins
    {
        $coins = $change->grantCoins();

        if (! $coins->isEmpty()) {
            try {
                app(Purse::class)->receive($character, $coins);
            } catch (MarketException $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        foreach ($change->grant_items ?? [] as $item) {
            $character->addToInventory(
                name: $item['name'],
                qty: (int) ($item['qty'] ?? 1),
                category: $item['category'] ?? null,
                valueCp: (int) ($item['value_cp'] ?? 0),
                details: $item['details'] ?? null,
                base: EquipmentSlot::isBase($item['base'] ?? null) ? $item['base'] : null,
                magicBonus: $this->bonus($item['magic_bonus'] ?? 0),
            );
        }

        return $coins;
    }

    /**
     * Si salvano nella richiesta: lo storico mostra quello che è stato davvero dato.
     *
     * @param  list<array<string,mixed>>  $items
     * @param  array<int, array<string,mixed>>  $fixes
     * @return list<array<string,mixed>>
     */
    private function fixItems(array $items, array $fixes): array
    {
        foreach ($fixes as $indice => $fix) {
            if (! isset($items[$indice])) {
                continue;
            }

            $base = $fix['base'] ?? null;
            $items[$indice]['base'] = EquipmentSlot::isBase($base) ? $base : null;
            $items[$indice]['magic_bonus'] = $this->bonus($fix['magic_bonus'] ?? 0);
        }

        return $items;
    }

    private function bonus(mixed $value): int
    {
        return max(0, min(CharacterItem::MAX_MAGIC_BONUS, (int) $value));
    }

    /**
     * L'effetto si lega all'oggetto (creato o già posseduto): vale finché è in
     * sintonia e sparisce se lo si vende. La sintonia si dà solo se c'è posto.
     */
    private function applyItemEffect(Character $character, PendingChange $change): Coins
    {
        $effect = $change->diff ?? [];
        $name = $effect['name'] ?? 'Oggetto magico';

        $item = $character->items()->where('name', $name)->first()
            ?? $character->addToInventory(name: $name, category: 'Oggetti Magici');

        $character->itemEffects()->create([
            'character_item_id' => $item->getKey(),
            'name' => $name,
            'ability' => $effect['ability'],
            'mode' => $effect['mode'],
            'value' => (int) $effect['value'],
        ]);

        if ($character->items()->where('attuned', true)->count() < Character::ATTUNEMENT_LIMIT) {
            $item->forceFill(['attuned' => true])->save();
        }

        return Coins::none();
    }

    /**
     * @param  array<string,mixed>|null  $changes
     * @param  list<string>  $fields
     * @return array<string,mixed>
     */
    private function allowed(?array $changes, array $fields): array
    {
        return array_intersect_key($changes ?? [], array_flip($fields));
    }
}
