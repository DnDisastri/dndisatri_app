<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'seller_character_id', 'name', 'category', 'qty', 'price', 'unit_value', 'details',
])]
class MarketListing extends Model
{
    use HasFactory;

    /** Vedi la nota in Trade: il predefinito del database non basta. */
    protected $attributes = ['status' => ListingStatus::Active->value];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'qty' => 'integer',
            'price' => 'integer',
            'unit_value' => 'integer',
            'resolved_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'seller_character_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'buyer_character_id');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /** La vetrina: solo ciò che è ancora comprabile. */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ListingStatus::Active);
    }

    public function scopeSoldBy(Builder $query, Character $character): void
    {
        $query->where('seller_character_id', $character->getKey());
    }
}
