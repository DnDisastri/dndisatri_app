<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un posto a una sessione: di un giocatore registrato, o di un ospite aggiunto da un DM. */
class SessionBooking extends Model
{
    protected $table = 'game_session_bookings';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => SeatStatus::class,
            'joined_at' => 'datetime',
            'decided_at' => 'datetime',
            'guest_attended' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /** Il nome da mostrare: il giocatore, o l'ospite. */
    public function displayName(): string
    {
        return $this->user?->name ?? (string) $this->guest_name;
    }

    /** Il personaggio da mostrare: quello della scheda, o quello scritto per l'ospite. */
    public function characterName(): ?string
    {
        return $this->character?->name ?? $this->guest_character;
    }

    public function scopeHoldingSeat(Builder $query): void
    {
        $query->whereIn('status', [SeatStatus::Booked->value, SeatStatus::Confirmed->value])->orderBy('joined_at');
    }

    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', SeatStatus::Waiting->value)->orderBy('joined_at');
    }
}
