<?php

namespace App\Models;

use App\Enums\EquipmentSlot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['character_id', 'name', 'category', 'qty', 'value', 'details'])]
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

    /**
     * Quello che il proprietario ha messo in vetrina per gli scambi.
     *
     * Il resto dello zaino non si vede da fuori, ed è una decisione presa: di
     * una scheda altrui non si vedono né inventario né oro.
     */
    public function scopeTradeable(Builder $query): void
    {
        $query->where('tradeable', true);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** Gli effetti che questo oggetto porta con sé. */
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

    /** Valore complessivo della riga: unitario per quantità. */
    public function totalValue(): int
    {
        return $this->value * $this->qty;
    }
}
