<?php

namespace App\Models;

use App\Domain\Dnd\Coins;
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
    'setting', 'rewards', 'reward_coins', 'reward_items', 'difficulty', 'type',
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
            'reward_coins' => 'array',
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

    /** Il capogilda del tavolo: vive sulla campagna (uno per campagna), le quest lo leggono da lì. */
    public function questGiver(): ?string
    {
        return $this->campaign?->quest_giver;
    }

    public function rewardCoins(): Coins
    {
        return Coins::fromArray($this->reward_coins);
    }

    /** Le quest devono avere una ricompensa: monete, oggetti o testo libero. */
    public function hasReward(): bool
    {
        return ! $this->rewardCoins()->isEmpty()
            || filled($this->reward_items)
            || filled($this->rewards);
    }

    public function isCampaign(): bool
    {
        return $this->type === QuestType::Campaign;
    }

    /** Tutte le prenotazioni, ritirati compresi (lo storico non si cancella). Per i partecipanti veri: `seatHolders()`. */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'joined_at', 'decided_at'])
            ->withTimestamps();
    }

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

    /** Il minimo è un'indicazione, non un divieto: dice al DM se la serata sta in piedi. */
    public function hasMinimum(): bool
    {
        return $this->participantCount() >= $this->min_participants;
    }

    public function missingToMinimum(): int
    {
        return max(0, $this->min_participants - $this->participantCount());
    }

    /** Il DM ha dichiarato che la serata si fa: è una proprietà, non si deduce dai confermati (uno che si ritira non l'annulla). */
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

    /** Solo da conclusa: si applaude com'è andata (su una aperta il gesto è già «voglio partecipare»). */
    public function acceptsReactions(): bool
    {
        return $this->isArchived();
    }
}
