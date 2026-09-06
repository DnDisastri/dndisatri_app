<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'title', 'slug', 'description', 'cover_path', 'background_path', 'background_opacity', 'season',
    'quest_giver', 'quest_giver_description', 'quest_giver_photo',
    'dm_id', 'created_by', 'ended_at',
])]
class Campaign extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('campagna')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'ended_at' => 'datetime',
            'season' => 'integer',
            'background_opacity' => 'integer',
        ];
    }

    /** Il dungeon master del tavolo: da lui derivano tutti i permessi. */
    public function dm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dm_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    /** Le serate di gioco: calendario e storico dei recap. */
    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Il tavolo: i personaggi vivi che hanno giocato questa campagna. Non è un
     * elenco fisso, si ricava dalle presenze (`game_session_user`). Carica
     * oggetti ed effetti per i PF efficaci (la barra del DM deve dire il numero giusto).
     */
    public function roster(): \Illuminate\Database\Eloquent\Collection
    {
        return Character::query()
            ->alive()
            ->whereIn('id', GameSession::query()
                ->where('campaign_id', $this->getKey())
                ->join('game_session_user', 'game_sessions.id', '=', 'game_session_user.game_session_id')
                ->select('game_session_user.character_id'))
            ->with(['user', 'items', 'itemEffects'])
            ->orderBy('name')
            ->get();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    public function scopeEnded(Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }

    /** Le campagne di cui questo utente è il DM. */
    public function scopeRunBy(Builder $query, User $dm): void
    {
        $query->where('dm_id', $dm->getKey());
    }

    // === Il capogilda ===

    /** L'NPC del DM che affida gli incarichi: uno per campagna, vive qui (non su una tabella sua). */
    public function hasQuestGiver(): bool
    {
        return filled($this->quest_giver);
    }

    public function questGiverPhotoUrl(): ?string
    {
        return $this->quest_giver_photo
            ? Storage::disk('public')->url($this->quest_giver_photo)
            : null;
    }

    /** La copertina, se c'è: l'elenco delle campagne regge anche senza. */
    public function coverUrl(): ?string
    {
        return $this->cover_path
            ? Storage::disk('public')->url($this->cover_path)
            : null;
    }

    /**
     * Lo sfondo della pagina, che ricade sulla copertina se non ce n'è uno suo
     * (una pagina senza fondo sembra rotta). Lo sfondo dedicato serve quando la
     * copertina ha un soggetto forte che dietro al testo fa rumore.
     */
    public function backgroundUrl(): ?string
    {
        return $this->background_path
            ? Storage::disk('public')->url($this->background_path)
            : $this->coverUrl();
    }

    /** L'opacità del velo sullo sfondo, 0-1 (default 0.85). */
    public function backgroundVeil(): float
    {
        return (int) ($this->background_opacity ?? 85) / 100;
    }

    // === Query ===

    public function scopeInSeason(Builder $query, int $season): void
    {
        $query->where('season', $season);
    }

    /**
     * Le season esistenti, dalla più recente (l'elenco del filtro): ricavate
     * dalle campagne, così non propone una season vuota né ne dimentica una.
     *
     * @return list<int>
     */
    public static function seasons(): array
    {
        return static::query()
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season')
            ->map(fn ($season) => (int) $season)
            ->all();
    }
}
