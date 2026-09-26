<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffect;
use App\Domain\Dnd\ItemEffectMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['character_id', 'character_item_id', 'name', 'ability', 'mode', 'value'])]
class CharacterItemEffect extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ability' => Ability::class,
            'mode' => ItemEffectMode::class,
            'value' => 'integer',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** Passa dalla riga di database all'oggetto di dominio che fa il calcolo. */
    public function toDomain(): ItemEffect
    {
        return new ItemEffect($this->ability, $this->mode, $this->value, $this->name);
    }

    public function describe(): string
    {
        $sign = $this->mode === ItemEffectMode::Set
            ? 'porta a'
            : ($this->value >= 0 ? '+' : '');

        return "{$this->name}: {$this->ability->label()} {$sign}{$this->value}";
    }
}
