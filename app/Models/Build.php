<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una build consigliata: un personaggio di 1° già pensato.
 *
 * Serve a chi si affaccia al gioco e non ha voglia di studiarsi il manuale
 * prima di sedersi al tavolo. La scrive un dungeon master dal pannello.
 */
#[Fillable([
    'title', 'slug', 'tag', 'summary', 'body', 'abilities_advice', 'progression',
    'cover_path', 'class', 'subclass', 'species', 'background',
    'scores', 'species_choices', 'skills', 'equipment', 'spells',
    'created_by', 'published_at',
])]
class Build extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('build')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'species_choices' => 'array',
            'skills' => 'array',
            'equipment' => 'array',
            'spells' => 'array',
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

    // === Pubblicazione ===

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /** Quello che vedono i giocatori. */
    public function scopePublished(EloquentBuilder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('class');
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path
            ? Storage::disk('public')->url($this->cover_path)
            : null;
    }

    // === Il passaggio alla creazione guidata ===

    /**
     * È completa se basta a riempire un personaggio di 1° senza domande.
     *
     * Le otto arrivate dalla vecchia applicazione **non lo sono**: avevano solo
     * classe, sottoclasse e un consiglio a parole. Finché un DM non le
     * completa, «usa questa build» porta avanti quel che c'è e lascia il resto
     * da scegliere, che è meglio di un pulsante che non compare.
     */
    public function isComplete(): bool
    {
        return filled($this->species)
            && filled($this->background)
            && filled($this->scores)
            && filled($this->skills);
    }

    /**
     * Lo stato da cui parte il mago della creazione.
     *
     * Restituisce **solo le caselle che la build sa riempire**: le chiavi
     * assenti non vanno impostate a vuoto, o cancellerebbero i valori di
     * partenza del modulo (i punteggi del point buy su tutti).
     *
     * @return array<string,mixed>
     */
    public function wizardState(): array
    {
        return array_filter([
            'class' => $this->class,
            'species' => $this->species,
            'background' => $this->background,
            'scores' => $this->scores,
            'speciesChoices' => $this->species_choices,
            'skills' => $this->skills,
            'equipment' => $this->equipment,
            'spells' => $this->spells,
        ], fn ($value) => filled($value));
    }
}
