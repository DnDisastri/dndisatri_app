<?php

namespace App\Models;

use App\Enums\EncounterStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un combattimento: si prepara, si conduce in sessione, si conclude. I PF
 * degli eroi sono quelli veri della scheda; mostri e ospiti vivono solo in `combatants`.
 */
#[Fillable(['campaign_id', 'game_session_id', 'title'])]
class Encounter extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'prepared', 'round' => 1];

    protected function casts(): array
    {
        return [
            'status' => EncounterStatus::class,
            'combatants' => 'array',
            'round' => 'integer',
            'ended_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEnded(): bool
    {
        return $this->status === EncounterStatus::Ended;
    }

    /** I mostri a zero PF: lo storico dice chi è caduto. */
    public function defeated(): array
    {
        return collect($this->combatants ?? [])
            ->where('tipo', 'mostro')
            ->filter(fn (array $c) => (int) ($c['hp'] ?? 1) <= 0)
            ->pluck('nome')
            ->values()
            ->all();
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', EncounterStatus::Ended);
    }
}
