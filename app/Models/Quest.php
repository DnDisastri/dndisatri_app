<?php

namespace App\Models;

use App\Domain\Dnd\Coins;
use App\Enums\QuestDifficulty;
use App\Enums\QuestOutcome;
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
    'created_by',
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

    /** Il capogilda sta sulla campagna, uno per campagna. */
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

    /** La sessione in cui il DM l'ha messa; null finché non si sa quando si gioca. */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    /** Chi ha detto «mi interessa»: non è una prenotazione, ci si prenota alla sessione. */
    public function interested(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('joined_at')
            ->withTimestamps()
            ->orderByPivot('joined_at');
    }

    public function isInterested(User $user): bool
    {
        return $this->interested()->whereKey($user->getKey())->exists();
    }

    /** Messa in una sessione che deve ancora giocarsi. */
    public function isScheduled(): bool
    {
        return $this->session !== null && $this->session->isUpcoming();
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

    /** Solo da conclusa: si applaude com'è andata (su una aperta il gesto è già «mi interessa»). */
    public function acceptsReactions(): bool
    {
        return $this->isArchived();
    }
}
