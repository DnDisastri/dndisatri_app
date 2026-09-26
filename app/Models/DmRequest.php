<?php

namespace App\Models;

use App\Enums\PendingChangeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'message'])]
class DmRequest extends Model
{
    use HasFactory, LogsActivity;

    /** Vedi la nota in Trade: il predefinito del database non basta. */
    protected $attributes = ['status' => PendingChangeStatus::Pending->value];

    /**
     * Una promozione a DM è la cosa più delicata che succede nel sistema:
     * il registro deve conservarne traccia per sempre.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('richiesta-dm')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => PendingChangeStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === PendingChangeStatus::Pending;
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PendingChangeStatus::Pending);
    }
}
