<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un posto a una sessione: di un giocatore registrato, o di un ospite senza account. */
class SessionBooking extends Model
{
    /** Quanto ha per confermare chi riceve un posto, salvo che la sessione cominci prima. */
    public const OFFER_HOURS = 24;

    protected $table = 'game_session_bookings';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => SeatStatus::class,
            'joined_at' => 'datetime',
            'decided_at' => 'datetime',
            'offer_expires_at' => 'datetime',
            'reserve_asked_at' => 'datetime',
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

    public function contactEmail(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    public function contactPhone(): ?string
    {
        return $this->user?->phone ?? $this->guest_phone;
    }

    /** Il nome utente Instagram o Telegram di un ospite aggiunto dal DM. */
    public function contactSocial(): ?string
    {
        return $this->guest_social;
    }

    /** Senza email l'ospite non può rispondere dall'app: lo contatta il DM. */
    public function answersOutsideApp(): bool
    {
        return $this->isGuest() && blank($this->guest_email);
    }

    /** Alla sessione piena gli si è chiesto se resta come riserva, e non ha ancora risposto. */
    public function awaitsReserveAnswer(): bool
    {
        return $this->status === SeatStatus::Requested && $this->reserve_asked_at !== null;
    }

    /** La pagina dell'ospite, senza account: il token è il suo accesso. */
    public function guestUrl(): ?string
    {
        return $this->guest_token ? route('guest-bookings.show', $this->guest_token) : null;
    }

    public function scopeHoldingSeat(Builder $query): void
    {
        $query->whereIn('status', SeatStatus::seatValues())->orderBy('joined_at');
    }

    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', SeatStatus::Confirmed->value)->orderBy('joined_at');
    }

    /** Quello che il DM vede: tutti tranne gli ospiti non verificati e chi si è ritirato, in ordine di arrivo. */
    public function scopeVisibleToDm(Builder $query): void
    {
        $query->whereNotIn('status', [SeatStatus::Unverified->value, SeatStatus::Withdrawn->value])->orderBy('joined_at');
    }

    public function scopeReserves(Builder $query): void
    {
        $query->where('status', SeatStatus::Reserve->value)->orderBy('joined_at');
    }
}
