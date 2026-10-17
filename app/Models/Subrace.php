<?php

namespace App\Models;

use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una sottorazza: il drow per l'elfo, il nano delle colline per il nano.
 *
 * Porta bonus alle caratteristiche che si sommano a quelli della razza, e se
 * ha una velocità propria sostituisce la sua.
 */
#[Fillable(['race', 'rules_edition', 'name', 'description', 'asi', 'speed', 'traits', 'is_homebrew', 'position'])]
class Subrace extends Model
{
    use HasFactory, LogsActivity;

    protected $attributes = ['rules_edition' => '2014'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sottorazze')
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
            'is_homebrew' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopeOfRace(Builder $query, ?string $race): void
    {
        $query->where('race', $race);
    }

    /** Nell'ordine del manuale, che il seeder conserva. */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
