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
 * Un mostro del bestiario: lo statblock riusabile che il DM pesca nel tracker.
 *
 * È **materiale condiviso**, come un manuale: lo scrive un DM e lo usano tutti,
 * perché un repertorio di mostri è tanto più utile quanto più cresce. Nel
 * tracker il mostro scelto viene **copiato** nella serata — i PF calano lì, non
 * qui: il bestiario è la scheda pulita, non il mostro di stasera.
 */
#[Fillable([
    'name', 'hp', 'ac', 'speed', 'attacks', 'traits', 'created_by',
])]
class Monster extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mostro')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'attacks' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch(Builder $query, string $termine): void
    {
        $query->where('name', 'like', '%'.$termine.'%');
    }

    /**
     * La forma in cui il mostro entra nel tracker: l'essenziale per combattere
     * più lo statblock, così il modale esteso ha di che riempirsi anche quando
     * il bestiario non è a portata. I PF sono a pieno: lo scontro li muove.
     *
     * @return array<string, mixed>
     */
    public function toCombatant(): array
    {
        return [
            'nome' => $this->name,
            'hp' => (int) $this->hp,
            'hpMax' => (int) $this->hp,
            'ac' => (int) $this->ac,
            'speed' => $this->speed,
            'attacks' => $this->attacks ?? [],
            'traits' => $this->traits,
            'monsterId' => $this->getKey(),
        ];
    }
}
