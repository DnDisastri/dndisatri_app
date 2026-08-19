<?php

namespace App\Models;

use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Progression;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una delle classi di un personaggio, col suo livello.
 *
 * Quello che dipende da **questa** classe e non dal personaggio: il dado vita,
 * la sottoclasse e il livello a cui si sceglie, l'Esperto di Ladro e Bardo.
 *
 * Gli aumenti di caratteristica invece no: il gruppo li tiene sul livello
 * totale (D18), che è una deviazione voluta dal manuale.
 */
#[Fillable(['character_id', 'class', 'subclass', 'level', 'is_primary'])]
class CharacterClass extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function hitDie(): int
    {
        return ClassRules::hitDie($this->class);
    }

    public function casterType(): CasterType
    {
        return CasterType::for($this->class, $this->subclass);
    }

    /** Il livello di QUESTA classe a cui si sceglie la sottoclasse. */
    public function subclassLevel(): int
    {
        return Progression::subclassLevel($this->class);
    }

    public function needsSubclass(): bool
    {
        return $this->subclass === null && $this->level >= $this->subclassLevel();
    }

    /** «Guerriero 3», o «Guerriero (Campione) 3» se la sottoclasse c'è. */
    public function label(): string
    {
        return $this->subclass === null
            ? "{$this->class} {$this->level}"
            : "{$this->class} ({$this->subclass}) {$this->level}";
    }

    public function scopePrimary(Builder $query): void
    {
        $query->where('is_primary', true);
    }
}
