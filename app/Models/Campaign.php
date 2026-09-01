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
     * Il tavolo: i personaggi vivi che hanno davvero giocato questa campagna.
     *
     * Non è un elenco fisso — un personaggio non «appartiene» a una campagna —
     * si ricava da chi si è seduto al tavolo, cioè dal pivot delle presenze
     * (`game_session_user`) delle serate. È la fonte più onesta che abbiamo di
     * «chi c'è a questo tavolo».
     *
     * Carica quel che serve ai **PF efficaci** (oggetti e loro effetti): sul
     * cruscotto del DM la barra dei punti ferita deve dire il numero giusto,
     * non quello salvato senza gli oggetti in sintonia.
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

    /**
     * L'NPC del dungeon master: affida gli incarichi e fa succedere le serate.
     *
     * Vive qui e non su una tabella sua perché è di questa storia: un tavolo,
     * un capogilda. Se un giorno due capigilda dovranno collaborare come dato
     * e non solo a parole, si promuove allora.
     */
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
     * Lo sfondo della pagina della campagna.
     *
     * **Ricade sulla copertina quando non ce n'è uno suo**, e non è pigrizia:
     * una pagina che perde il fondo perché nessuno ha caricato la seconda
     * immagine sembra rotta, mentre la copertina sotto il velo funziona quasi
     * sempre. Lo sfondo dedicato serve quando la copertina ha un soggetto
     * forte — un volto, una scritta grande — che dietro al testo diventa
     * rumore.
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
     * Le season esistenti, dalla più recente: è l'elenco del filtro.
     *
     * Si ricava dalle campagne invece di essere scritta da qualche parte,
     * così non può mai proporre una season vuota né dimenticarne una.
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
