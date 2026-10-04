<?php

namespace App\Models;

use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una riga del Registro: si scrive e non si tocca più. */
#[Fillable(['character_id', 'actor_id', 'action', 'cp_delta', 'coins_delta', 'coins_after', 'message', 'details'])]
class LedgerEntry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'action' => LedgerAction::class,
            'cp_delta' => 'integer',
            'coins_delta' => 'array',
            'coins_after' => 'array',
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

    public function coinsDelta(): Coins
    {
        return Coins::fromArray($this->coins_delta);
    }

    /** Null per le righe che non hanno mai registrato la borsa. */
    public function coinsAfter(): ?Coins
    {
        return $this->coins_after === null ? null : Coins::fromArray($this->coins_after);
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('id');
    }

    public function scopeForCharacter(Builder $query, Character $character): void
    {
        $query->where('character_id', $character->getKey());
    }
}
