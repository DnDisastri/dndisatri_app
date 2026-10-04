<?php

namespace App\Models;

use App\Enums\EquipmentSlot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['character_id', 'name', 'base', 'magic_bonus', 'category', 'qty', 'value_cp', 'details'])]
class CharacterItem extends Model
{
    use HasFactory;

    /** Le categorie fra cui si sceglie nei moduli: le stesse del negozio. */
    public const CATEGORIES = [
        'Armi', 'Armature', 'Pozioni', 'Pozioni Rare', 'Veleni', 'Oggetti Magici', 'Kit e Strumenti', 'Varie',
    ];

    public const MAX_MAGIC_BONUS = 3;

    protected $attributes = ['magic_bonus' => 0];

    protected function casts(): array
    {
        return [
            'equipped_slot' => EquipmentSlot::class,
            'attuned' => 'boolean',
            'tradeable' => 'boolean',
            'magic_bonus' => 'integer',
        ];
    }

    /** La vetrina: il resto dello zaino non si vede da fuori. */
    public function scopeTradeable(Builder $query): void
    {
        $query->where('tradeable', true);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function effects(): HasMany
    {
        return $this->hasMany(CharacterItemEffect::class);
    }

    /** Il nome con cui l'oggetto si cerca nel catalogo di combattimento. */
    public function catalogKey(): string
    {
        return $this->base ?? $this->name;
    }

    public function naturalSlot(): ?EquipmentSlot
    {
        return EquipmentSlot::naturalFor($this->catalogKey());
    }

    /** «Armatura a Piastre +1» sotto un nome suo; null se non c'è niente da aggiungere. */
    public function baseLabel(): ?string
    {
        $bonus = $this->magic_bonus > 0 ? "+{$this->magic_bonus}" : null;
        $base = $this->base !== null && $this->base !== $this->name ? $this->base : null;

        return ($base === null && $bonus === null) ? null : trim(($base ?? '').' '.($bonus ?? ''));
    }

    public function isEquipped(): bool
    {
        return $this->equipped_slot !== null;
    }

    public function scopeEquipped(Builder $query): void
    {
        $query->whereNotNull('equipped_slot');
    }

    public function scopeInSlot(Builder $query, EquipmentSlot $slot): void
    {
        $query->where('equipped_slot', $slot);
    }

    /** In rame. */
    public function totalValue(): int
    {
        return $this->value_cp * $this->qty;
    }
}
