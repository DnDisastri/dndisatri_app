<?php

namespace App\Models;

use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un evento del gruppo: raduno, one-shot, serata speciale.
 *
 * Da non confondere con GameSession, che è la serata di gioco di una campagna
 * e ha il suo recap.
 */
#[Fillable([
    'created_by', 'title', 'slug', 'description', 'cover_path',
    'starts_at', 'ends_at', 'location', 'published_at',
])]
class Event extends Model
{
    use HasFactory, HasReactions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('evento')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture();
    }

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** I prossimi eventi, dal più vicino. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopePast(Builder $query): void
    {
        $query->where('starts_at', '<', now())->orderByDesc('starts_at');
    }

    /** Come per le news: prima della pubblicazione non c'è niente da applaudire. */
    public function acceptsReactions(): bool
    {
        return $this->isPublished();
    }
}
