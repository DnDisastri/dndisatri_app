<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una voce della guida: una domanda con la sua risposta, dentro una sezione.
 */
#[Fillable([
    'category', 'question', 'answer', 'position', 'is_published', 'show_public', 'show_private',
])]
class Faq extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('guida')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_published' => 'boolean',
            'show_public' => 'boolean',
            'show_private' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Quelle della pagina pubblica (`false`) o di quella interna (`true`). */
    public function scopeFor(Builder $query, bool $dentro): void
    {
        $query->where($dentro ? 'show_private' : 'show_public', true);
    }

    /** Per posizione, poi id a parità. */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
