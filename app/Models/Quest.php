<?php

namespace App\Models;

use App\Enums\QuestDifficulty;
use App\Enums\QuestOutcome;
use App\Enums\QuestSeatStatus;
use App\Enums\QuestType;
use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'campaign_id', 'title', 'slug', 'description',
    'setting', 'rewards', 'reward_gold', 'reward_items', 'difficulty', 'type',
    'min_participants', 'max_participants', 'created_by',
])]
class Quest extends Model
{
    use HasFactory, HasReactions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('quest')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'difficulty' => QuestDifficulty::class,
            'type' => QuestType::class,
            'reward_gold' => 'integer',
            'reward_items' => 'array',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
            'night_confirmed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Chi affida l'incarico: il capogilda del tavolo.
     *
     * Vive sulla campagna — **uno per campagna**, non uno per DM: è l'NPC di
     * quella storia, e un DM che apre due tavoli diversi può averne due
     * diversi. Le quest non ne tengono una copia: la leggono da lì, così non
     * c'è niente da riallineare quando il DM lo cambia.
     */
    public function questGiver(): ?string
    {
        return $this->campaign?->quest_giver;
    }

    /**
     * Ha una ricompensa da mostrare?
     *
     * Ne basta una parte: oro, oggetti, o il campo libero. Le quest **devono**
     * averne una — lo garantisce il modulo del dungeon master — e questo è il
     * conto che lo verifica.
     */
    public function hasReward(): bool
    {
        return (int) $this->reward_gold > 0
            || filled($this->reward_items)
            || filled($this->rewards);
    }

    /** Legata a una campagna e a una storia: l'unico tipo che esiste per ora. */
    public function isCampaign(): bool
    {
        return $this->type === QuestType::Campaign;
    }

    /**
     * Tutte le prenotazioni, **ritirati compresi**.
     *
     * Lo storico di chi voleva giocare non si cancella: è metà del motivo per
     * cui le prenotazioni esistono. Chi vuole i partecipanti veri usa
     * `seatHolders()`, non questa.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'joined_at', 'decided_at'])
            ->withTimestamps();
    }

    /** Chi occupa un posto: prenotati e confermati. */
    public function seatHolders(): BelongsToMany
    {
        return $this->participants()->wherePivotIn('status', [
            QuestSeatStatus::Booked->value,
            QuestSeatStatus::Confirmed->value,
        ]);
    }

    public function booked(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', QuestSeatStatus::Booked->value);
    }

    public function confirmed(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', QuestSeatStatus::Confirmed->value);
    }

    /** La lista d'attesa, in ordine di arrivo: chi ci ha pensato prima entra prima. */
    public function waiting(): BelongsToMany
    {
        return $this->participants()
            ->wherePivot('status', QuestSeatStatus::Waiting->value)
            ->orderByPivot('joined_at');
    }

    // === Ciclo di vita ===

    public function outcome(): QuestOutcome
    {
        return match (true) {
            $this->completed_at !== null => QuestOutcome::Completed,
            $this->closed_at !== null => QuestOutcome::Closed,
            default => QuestOutcome::Active,
        };
    }

    public function isActive(): bool
    {
        return $this->outcome() === QuestOutcome::Active;
    }

    public function isArchived(): bool
    {
        return $this->outcome()->isArchived();
    }

    // === Posti ===

    /** Quanti posti sono occupati: prenotati più confermati. */
    public function participantCount(): int
    {
        return $this->seatHolders()->count();
    }

    public function freeSlots(): int
    {
        return max(0, $this->max_participants - $this->participantCount());
    }

    public function isFull(): bool
    {
        return $this->freeSlots() === 0;
    }

    /**
     * Il minimo è **un'indicazione, non un divieto**: dice al dungeon master
     * se la serata sta in piedi, e resta lui a decidere se farla lo stesso.
     */
    public function hasMinimum(): bool
    {
        return $this->participantCount() >= $this->min_participants;
    }

    public function missingToMinimum(): int
    {
        return max(0, $this->min_participants - $this->participantCount());
    }

    /**
     * Il dungeon master ha dichiarato che la serata si fa.
     *
     * È una proprietà della quest e non si deduce dai posti confermati: se
     * l'ultimo confermato si ritirasse, una serata dichiarata tornerebbe in
     * forse da sola.
     */
    public function isNightConfirmed(): bool
    {
        return $this->night_confirmed_at !== null;
    }

    // === Il posto di un giocatore ===

    public function seatOf(User $user): ?QuestSeatStatus
    {
        $stato = $this->participants()
            ->whereKey($user->getKey())
            ->first()?->pivot?->status;

        return $stato === null ? null : QuestSeatStatus::from($stato);
    }

    /** Occupa un posto o è in lista: chi si è ritirato non conta. */
    public function hasParticipant(User $user): bool
    {
        return $this->seatOf($user)?->isActive() ?? false;
    }

    public function holdsSeat(User $user): bool
    {
        return $this->seatOf($user)?->takesSeat() ?? false;
    }

    // === Query ===

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('completed_at')->whereNull('closed_at');
    }

    /** Il Libro Mastro: completate e chiuse insieme, dalle più recenti. */
    public function scopeArchived(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNotNull('completed_at')->orWhereNotNull('closed_at'));
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    public function scopeClosed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    /**
     * Solo da conclusa, e per lo stesso motivo del resoconto: si applaude
     * **com'è andata**. Su una quest ancora aperta il gesto c'è già ed è
     * «voglio partecipare» — una faccina accanto sarebbe più facile da dare e
     * direbbe molto meno.
     */
    public function acceptsReactions(): bool
    {
        return $this->isArchived();
    }
}
