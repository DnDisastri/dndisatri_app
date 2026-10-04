<?php

namespace App\Models;

use App\Enums\EquipmentSlot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['character_id', 'name', 'category', 'qty', 'value_cp', 'details'])]
class CharacterItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'equipped_slot' => EquipmentSlot::class,
            'attuned' => 'boolean',
            'tradeable' => 'boolean',
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
