<?php

namespace App\Models;

use App\Enums\TradeDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trade_id', 'direction', 'name', 'base', 'magic_bonus', 'effects', 'category', 'qty', 'value_cp', 'details'])]
class TradeItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'direction' => TradeDirection::class,
            'qty' => 'integer',
            'value_cp' => 'integer',
            'effects' => 'array',
        ];
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }
}
