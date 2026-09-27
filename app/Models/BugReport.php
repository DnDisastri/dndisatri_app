<?php

namespace App\Models;

use App\Enums\BugReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un problema segnalato da un giocatore o da un dungeon master.
 *
 * Lo stato non è mass-assignable: chiudere una segnalazione passa dal gesto
 * esplicito nel pannello, che avvisa anche chi l'ha aperta.
 */
#[Fillable(['user_id', 'title', 'description', 'page', 'user_agent'])]
class BugReport extends Model
{
    use HasFactory;

    protected $attributes = ['status' => BugReportStatus::Open->value];

    protected function casts(): array
    {
        return [
            'status' => BugReportStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    /** Chi ha segnalato. Nullo se nel frattempo ha lasciato il gruppo. */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /** Quelle su cui tocca ancora fare qualcosa. */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [BugReportStatus::Open, BugReportStatus::InProgress]);
    }
}
