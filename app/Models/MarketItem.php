<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** `price_cp` è in rame. */
#[Fillable(['name', 'category', 'price_cp', 'is_unlimited', 'stock', 'details'])]
class MarketItem extends Model
{
    use HasFactory, LogsActivity;

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
            'price_cp' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function isAvailable(int $qty = 1): bool
    {
        return $this->is_unlimited || $this->stock >= $qty;
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('is_unlimited', true)->orWhere('stock', '>', 0));
    }

    public function totalPrice(int $qty): int
    {
        return $this->price_cp * $qty;
    }
}
