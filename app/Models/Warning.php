<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un richiamo a un giocatore (D13).
 *
 * Finché è attivo, le sue azioni di mercato passano dall'approvazione di un DM
 * o di un admin.
 */
#[Fillable(['user_id', 'issued_by', 'reason'])]
class Warning extends Model
{
    use HasFactory, LogsActivity;

    /** Dare e togliere un richiamo sono atti che restano, per sempre. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('richiamo')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['lifted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function isActive(): bool
    {
        return $this->lifted_at === null;
    }

    /** Quanti giorni è durato, o dura finora se è ancora aperto. */
    public function daysLasted(): int
    {
        return (int) $this->created_at->diffInDays($this->lifted_at ?? now());
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('lifted_at');
    }

    public function scopeLifted(Builder $query): void
    {
        $query->whereNotNull('lifted_at');
    }
}
