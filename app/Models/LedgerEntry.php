<?php

namespace App\Models;

use App\Enums\LedgerAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una riga del Registro. Si scrive e non si tocca più.
 */
#[Fillable(['character_id', 'actor_id', 'action', 'gp_delta', 'gp_after', 'message', 'details'])]
class LedgerEntry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'action' => LedgerAction::class,
            'gp_delta' => 'integer',
            'details' => 'array',
            'reversed_at' => 'datetime',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Dal movimento più recente: è l'ordine in cui si legge un registro. */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('id');
    }

    public function scopeForCharacter(Builder $query, Character $character): void
    {
        $query->where('character_id', $character->getKey());
    }
}
