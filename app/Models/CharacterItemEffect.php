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
        return "{$this->name}: ".self::describeCopy($this->toCopy());
    }

    /**
     * Quello che serve a ricrearlo su un altro oggetto: il nome segue l'oggetto.
     *
     * @return array{ability: string, mode: string, value: int}
     */
    public function toCopy(): array
    {
        return ['ability' => $this->ability->value, 'mode' => $this->mode->value, 'value' => (int) $this->value];
    }

    /** @param  array{ability: string, mode: string, value: int}  $copia */
    public static function describeCopy(array $copia): string
    {
        $ability = Ability::from($copia['ability']);
        $mode = ItemEffectMode::from($copia['mode']);
        $value = (int) $copia['value'];

        $sign = $mode === ItemEffectMode::Set ? 'porta a ' : ($value >= 0 ? '+' : '');

        return "{$ability->label()} {$sign}{$value}";
    }
}
