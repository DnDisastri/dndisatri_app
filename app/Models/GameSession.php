<?php

namespace App\Models;

use App\Enums\SeatStatus;
use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una sessione di gioco: posti, prenotazioni, quest, presenze e resoconto.
 * Non `Session`, per non confondersi con le sessioni di login di Laravel.
 */
#[Fillable(['campaign_id', 'number', 'title', 'played_at', 'created_by', 'min_players', 'max_players'])]
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
            'players_notified_at' => 'datetime',
            'rewards' => 'array',
            'min_players' => 'integer',
            'max_players' => 'integer',
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

    /** Solo storico: i combattimenti vivono anche senza sessione. */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /** Le quest che il DM ha messo in questa sessione. */
    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    /** Tutti i posti, ospiti compresi. */
    public function bookings(): HasMany
    {
        return $this->hasMany(SessionBooking::class);
    }

    /** Le prenotazioni dei giocatori registrati, ritirati compresi. */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_session_bookings')
            ->withPivot(['character_id', 'status', 'joined_at', 'decided_at'])
            ->withTimestamps();
    }

    /** I personaggi dei posti confermati, per gli eroi del DM e il combattimento: un'offerta non è ancora un sì. */
    public function bookedCharacters(): Collection
    {
        $ids = $this->bookings()->confirmed()->pluck('character_id')->filter();

        return Character::query()
            ->alive()
            ->whereIn('id', $ids)
            ->with(['user', 'items', 'itemEffects'])
            ->orderBy('name')
            ->get();
    }

    public function recapWrittenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recap_written_by');
    }

    /** Chi c'era davvero, non chi si era iscritto. Il pivot porta anche il personaggio (nullo per chi conduce). */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('character_id')
            ->withTimestamps();
    }

    /** I personaggi che hanno giocato: dal pivot, non dai giocatori (conta «Grimm c'era», non «Marco»). */
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

    // === Posti ===

    /** Ci si prenota fino all'inizio della sessione. */
    public function acceptsBookings(): bool
    {
        return $this->isUpcoming();
    }

    /** Offerti e confermati, ospiti compresi: il posto è tenuto per loro. */
    public function participantCount(): int
    {
        return $this->bookings()->holdingSeat()->count();
    }

    public function confirmedCount(): int
    {
        return $this->bookings()->confirmed()->count();
    }

    public function freeSlots(): int
    {
        return max(0, $this->max_players - $this->participantCount());
    }

    /** Non ci sono posti da offrire: si può ancora chiedere, si finisce fra le richieste. */
    public function isFull(): bool
    {
        return $this->freeSlots() === 0;
    }

    /** Tutti i posti confermati: è il momento di chiedere agli altri se restano come riserve. */
    public function isFullyConfirmed(): bool
    {
        return $this->confirmedCount() >= $this->max_players;
    }

    /** Il minimo è un'indicazione per il DM, non un divieto. */
    public function missingToMinimum(): int
    {
        return max(0, $this->min_players - $this->confirmedCount());
    }

    public function hasMinimum(): bool
    {
        return $this->missingToMinimum() === 0;
    }

    public function bookingOf(User $user): ?SessionBooking
    {
        return $this->bookings()->where('user_id', $user->getKey())->first();
    }

    public function seatOf(User $user): ?SeatStatus
    {
        return $this->bookingOf($user)?->status;
    }

    /** Il personaggio con cui il giocatore si è prenotato. */
    public function bookedCharacterOf(User $user): ?int
    {
        return $this->players()->whereKey($user->getKey())->first()?->pivot?->character_id;
    }

    /** Ha chiesto un posto o ce l'ha: chi si è ritirato non conta. */
    public function hasParticipant(User $user): bool
    {
        return $this->seatOf($user)?->isActive() ?? false;
    }

    /** Un DM che non è quello della campagna: gli admin non sostituiscono, amministrano. */
    public function isSubstitute(User $user): bool
    {
        $titolare = $this->campaign?->dm_id;

        return $titolare !== null && ! $user->isAdmin() && $user->isDm() && $user->getKey() !== $titolare;
    }

    /** Per la firma «in sostituzione di». */
    public function recapBySubstitute(): bool
    {
        $titolare = $this->campaign?->dm_id;

        return $titolare !== null
            && $this->recap_written_by !== null
            && $this->recap_written_by !== $titolare
            && ! ($this->recapWrittenBy?->isAdmin() ?? false);
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

    /** Titolo da mostrare: "Sessione 12: La Torre Nera", o quel che c'è. */
    public function displayTitle(): string
    {
        return filled($this->title) ? "{$this->numberLabel()}: {$this->title}" : $this->numberLabel();
    }

    // === Query ===

    /** Le prossime, dalla più vicina. */
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

    /** Si reagisce al resoconto, quindi solo quando c'è. */
    public function acceptsReactions(): bool
    {
        return $this->hasRecap();
    }
}
