<?php

namespace App\Models;

use App\Domain\Dnd\BackgroundCatalogue;
use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un background. Nel 2024 dà anche le caratteristiche da aumentare e il
 * talento di origine. I personaggi lo indicano per nome, che non si cambia.
 */
#[Fillable([
    'name', 'rules_edition', 'skills', 'free_skills', 'gp', 'equipment_summary', 'feature',
    'ability_options', 'origin_feat', 'starting_items', 'position',
])]
class Background extends Model
{
    use LogsActivity;

    protected $attributes = ['rules_edition' => '2014', 'free_skills' => 0, 'gp' => 0, 'position' => 99];

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
            'skills' => 'array',
            'free_skills' => 'integer',
            'gp' => 'integer',
            'ability_options' => 'array',
            'starting_items' => 'array',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(BackgroundCatalogue::class));
        static::deleted(fn () => app()->forgetInstance(BackgroundCatalogue::class));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Personaggi o build che lo usano, con quelle regole: finché ci sono non si cancella. */
    public function isInUse(): bool
    {
        return Character::withTrashed()->where('background', $this->name)->where('rules_edition', $this->rules_edition->value)->exists()
            || ($this->rules_edition === RulesEdition::Classic && Build::where('background', $this->name)->exists());
    }
}
