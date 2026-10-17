<?php

namespace App\Models;

use App\Domain\Dnd\SpeciesCatalogue;
use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una specie giocabile. I personaggi, le sottospecie e le build la indicano
 * per nome: per questo il nome non si cambia dal pannello.
 */
#[Fillable([
    'name', 'rules_edition', 'asi', 'speed', 'traits', 'description',
    'free_bonuses', 'skill_choices', 'grants_feat', 'position',
])]
class Species extends Model
{
    use LogsActivity;

    protected $table = 'species';

    protected $attributes = ['rules_edition' => '2014', 'speed' => 9, 'free_bonuses' => 0, 'skill_choices' => 0, 'grants_feat' => false, 'position' => 99];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalogo')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'rules_edition' => RulesEdition::class,
            'asi' => 'array',
            'speed' => 'float',
            'free_bonuses' => 'integer',
            'skill_choices' => 'integer',
            'grants_feat' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(SpeciesCatalogue::class));
        static::deleted(fn () => app()->forgetInstance(SpeciesCatalogue::class));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Personaggi, sottospecie o build che la usano: finché ci sono non si cancella. */
    public function isInUse(): bool
    {
        return Character::withTrashed()->where('race', $this->name)->where('rules_edition', $this->rules_edition->value)->exists()
            || Subrace::where('race', $this->name)->where('rules_edition', $this->rules_edition->value)->exists()
            || ($this->rules_edition === RulesEdition::Classic && Build::where('species', $this->name)->exists());
    }
}
