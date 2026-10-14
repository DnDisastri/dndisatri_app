<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un tavolo di una sessione speciale: posti, DM e un resoconto facoltativo. */
#[Fillable(['name', 'seats', 'position'])]
class SessionTable extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'position' => 'integer',
            'recap_written_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // I posti della sessione sono la somma dei tavoli: si tiene allineata da qui.
        $allinea = fn (SessionTable $tavolo) => $tavolo->session?->syncSeatsFromTables();

        static::saved(fn (SessionTable $tavolo) => $tavolo->wasChanged('seats') || $tavolo->wasRecentlyCreated ? $allinea($tavolo) : null);
        static::deleted($allinea);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function dms(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'session_table_dm')->withTimestamps();
    }

    /** I posti assegnati a questo tavolo. */
    public function bookings(): HasMany
    {
        return $this->hasMany(SessionBooking::class);
    }

    public function recapWrittenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recap_written_by');
    }

    public function hasRecap(): bool
    {
        return filled($this->recap);
    }
}
