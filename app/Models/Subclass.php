<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una sottoclasse: il Cammino del Berserker per il Barbaro, e così via.
 *
 * Stavano in un file di configurazione generato dalla vecchia applicazione.
 * Ora sono righe, così il gruppo può aggiungerne di proprie senza toccare il
 * codice.
 */
#[Fillable(['class', 'name', 'description', 'third_caster', 'is_homebrew', 'position'])]
class Subclass extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sottoclassi')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'third_caster' => 'boolean',
            'is_homebrew' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopeOfClass(Builder $query, ?string $class): void
    {
        $query->where('class', $class);
    }

    /** Nell'ordine del manuale, che il seeder conserva. */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
