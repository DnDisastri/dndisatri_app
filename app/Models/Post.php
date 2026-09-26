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
 * Una news della gilda.
 */
#[Fillable([
    'author_id', 'title', 'slug', 'excerpt', 'body',
    'cover_path', 'published_at', 'is_pinned',
])]
class Post extends Model
{
    use HasFactory, HasReactions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('news')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Pubblicata se ha una data e quella data è passata: una data futura è
     * una pubblicazione programmata, non ancora visibile.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /** Quello che vedono i giocatori: in evidenza prima, poi per data. */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');
    }

    /**
     * Una news si applaude quando è uscita: su una bozza le reaction sarebbero
     * l'applauso di un admin a sé stesso.
     */
    public function acceptsReactions(): bool
    {
        return $this->isPublished();
    }
}
