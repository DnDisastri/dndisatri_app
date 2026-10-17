<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\EquipmentSlot;
use App\Enums\ItemRarity;
use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un oggetto magico del catalogo, da scegliere nei bottini e nelle ricompense:
 * l'oggetto dello zaino ne riceve una copia, effetti compresi.
 */
#[Fillable([
    'name', 'rules_edition', 'rarity', 'requires_attunement', 'category', 'base',
    'magic_bonus', 'effects', 'value_cp', 'details',
])]
class MagicItem extends Model
{
    use LogsActivity;

    protected $attributes = [
        'rules_edition' => '2014', 'rarity' => 'uncommon', 'requires_attunement' => false,
        'category' => 'Oggetti Magici', 'magic_bonus' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalogo')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'rules_edition' => RulesEdition::class,
            'rarity' => ItemRarity::class,
            'requires_attunement' => 'boolean',
            'magic_bonus' => 'integer',
            'effects' => 'array',
            'value_cp' => 'integer',
        ];
    }

    public function scopeOfEdition(Builder $query, RulesEdition $edition): void
    {
        $query->where('rules_edition', $edition->value)->orderBy('name');
    }

    /**
     * Una riga di bottino o ricompensa con l'id del catalogo ripulito: intero
     * se l'oggetto esiste, altrimenti tolto.
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    public static function linkRow(array $row): array
    {
        $id = filled($row['magic_item_id'] ?? null) ? (int) $row['magic_item_id'] : null;

        if ($id === null || ! self::whereKey($id)->exists()) {
            unset($row['magic_item_id']);

            return $row;
        }

        return [...$row, 'magic_item_id' => $id];
    }

    /**
     * Gli argomenti di `addToInventory` per una riga: il catalogo completa tipo,
     * bonus ed effetti, quello che la riga dice già vince.
     *
     * @param  array<string,mixed>  $row
     * @return array{base: ?string, magicBonus: int, effects: list<array{ability: string, mode: string, value: int}>|null}
     */
    public static function inventoryExtras(array $row): array
    {
        $catalogo = filled($row['magic_item_id'] ?? null) ? self::find((int) $row['magic_item_id']) : null;
        $base = EquipmentSlot::isBase($row['base'] ?? null) ? $row['base'] : null;

        return [
            'base' => $base ?? (EquipmentSlot::isBase($catalogo?->base) ? $catalogo->base : null),
            'magicBonus' => max(0, min(CharacterItem::MAX_MAGIC_BONUS, (int) (($row['magic_bonus'] ?? 0) ?: $catalogo?->magic_bonus))),
            'effects' => $catalogo?->effectsCopy() ?: null,
        ];
    }

    /** «Non comune, sintonia» davanti alla descrizione: è quello che il giocatore legge nello zaino. */
    public function detailsForInventory(): ?string
    {
        $testa = $this->rarity->label().($this->requires_attunement ? ', richiede sintonia' : '').'.';

        return trim($testa.' '.$this->details);
    }

    /**
     * Gli effetti nel formato di `Character::addToInventory`, scartando le righe incomplete.
     *
     * @return list<array{ability: string, mode: string, value: int}>
     */
    public function effectsCopy(): array
    {
        return collect($this->effects ?? [])
            ->filter(fn ($e) => Ability::tryFrom((string) ($e['ability'] ?? '')) && ItemEffectMode::tryFrom((string) ($e['mode'] ?? '')))
            ->map(fn (array $e) => ['ability' => $e['ability'], 'mode' => $e['mode'], 'value' => (int) ($e['value'] ?? 0)])
            ->values()
            ->all();
    }
}
