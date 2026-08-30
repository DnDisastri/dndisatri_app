<?php

namespace App\Models;

use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una serata di gioco: quando si gioca e cos'è successo.
 *
 * Il nome non è `Session` per non accavallarsi alle sessioni di login di
 * Laravel, che è anche il motivo per cui la tabella è `game_sessions`.
 */
#[Fillable(['campaign_id', 'number', 'title', 'played_at', 'created_by'])]
class GameSession extends Model
{
    use HasFactory, HasReactions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sessione')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'recap_written_at' => 'datetime',
            // L'ordine d'iniziativa: una lista corta che vive con la serata.
            'initiative' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recapWrittenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recap_written_by');
    }

    /**
     * Chi c'era davvero, non chi si era iscritto.
     *
     * Il pivot porta anche **con quale personaggio**: può essere nullo, perché
     * chi conduce siede al tavolo senza giocarne uno.
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('character_id')
            ->withTimestamps();
    }

    /**
     * I personaggi che hanno giocato questa serata.
     *
     * Passa dal pivot e non dai giocatori: è la differenza fra «c'era Marco» e
     * «c'era Grimm», e per una pagina di campagna conta la seconda.
     */
    public function playedCharacters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'game_session_user');
    }

    // === Stato ===

    public function isUpcoming(): bool
    {
        return $this->played_at->isFuture();
    }

    public function hasRecap(): bool
    {
        return filled($this->recap);
    }

    public function attended(User $user): bool
    {
        return $this->relationLoaded('attendees')
            ? $this->attendees->contains($user)
            : $this->attendees()->whereKey($user->getKey())->exists();
    }

    /** Solo la parte "Sessione 12", senza il titolo: serve dove le due righe si impilano. */
    public function numberLabel(): string
    {
        return $this->number !== null ? "Sessione {$this->number}" : 'Sessione';
    }

    /** Titolo da mostrare: "Sessione 12 — La Torre Nera", o quel che c'è. */
    public function displayTitle(): string
    {
        return filled($this->title) ? "{$this->numberLabel()} — {$this->title}" : $this->numberLabel();
    }

    // === Query ===

    /** Il calendario dei tavoli: cosa si gioca prossimamente. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('played_at', '>=', now())->orderBy('played_at');
    }

    /** Lo storico, dalla più recente. */
    public function scopePast(Builder $query): void
    {
        $query->where('played_at', '<', now())->orderByDesc('played_at');
    }

    public function scopeWithRecap(Builder $query): void
    {
        $query->whereNotNull('recap')->where('recap', '!=', '');
    }

    /**
     * La reaction è al **resoconto**, non alla serata.
     *
     * Prima che sia scritto questa pagina dice soltanto quando si gioca, e
     * applaudire una data non vuol dire niente. È anche il motivo per cui la
     * fila di faccine sta dentro il riquadro del racconto e non in fondo.
     */
    public function acceptsReactions(): bool
    {
        return $this->hasRecap();
    }
}
