<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** `price_cp` è in rame. */
#[Fillable(['name', 'base', 'magic_bonus', 'effects', 'category', 'price_cp', 'is_unlimited', 'stock', 'details'])]
class MarketItem extends Model
{
    use HasFactory, LogsActivity;

    protected $attributes = ['magic_bonus' => 0, 'in_storage' => false];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mercato')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_unlimited' => 'boolean',
            'in_storage' => 'boolean',
            'price_cp' => 'integer',
            'stock' => 'integer',
            'magic_bonus' => 'integer',
            'effects' => 'array',
        ];
    }

    /** In magazzino non si compra: aspetta che un DM o un admin gli dia un prezzo. */
    public function isAvailable(int $qty = 1): bool
    {
        return ! $this->in_storage && ($this->is_unlimited || $this->stock >= $qty);
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('in_storage', false)
            ->where(fn (Builder $q) => $q->where('is_unlimited', true)->orWhere('stock', '>', 0));
    }

    public function scopeOnSale(Builder $query): void
    {
        $query->where('in_storage', false);
    }

    public function scopeInStorage(Builder $query): void
    {
        $query->where('in_storage', true);
    }

    public function totalPrice(int $qty): int
    {
        return $this->price_cp * $qty;
    }
}
