<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\FeatureCatalogue;
use App\Enums\ActionCost;
use App\Enums\FeatureUsesFormula;
use App\Enums\RestType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un privilegio di classe o sottoclasse, dal livello in cui si prende. Gli usi
 * sono in numeri (quanti, come tornano, cosa consumano) e anche a parole, per
 * chi li legge sulla scheda.
 */
#[Fillable([
    'owner_type', 'owner', 'level', 'name', 'cost', 'text', 'uses_text', 'on_turn', 'our_name', 'to_check',
    'uses_formula', 'uses_value', 'uses_ability', 'uses_table', 'recharge', 'short_rest_from_level',
    'spends', 'spends_amount', 'can_activate', 'active_text', 'position',
])]
class ClassFeature extends Model
{
    use LogsActivity;

    public const CLASS_OWNER = 'class';

    public const SUBCLASS_OWNER = 'subclass';

    protected $attributes = ['on_turn' => true, 'our_name' => false, 'to_check' => false, 'spends_amount' => 1, 'can_activate' => false, 'position' => 0];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalogo')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'cost' => ActionCost::class,
            'on_turn' => 'boolean',
            'our_name' => 'boolean',
            'to_check' => 'boolean',
            'uses_formula' => FeatureUsesFormula::class,
            'uses_value' => 'integer',
            'uses_ability' => Ability::class,
            'uses_table' => 'array',
            'recharge' => RestType::class,
            'short_rest_from_level' => 'integer',
            'spends_amount' => 'integer',
            'can_activate' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(FeatureCatalogue::class));
        static::deleted(fn () => app()->forgetInstance(FeatureCatalogue::class));
    }

    /** Si usa durante o dopo un riposo breve, come il Recupero arcano: il modale del riposo lo ricorda. */
    public function usableOnShortRest(): bool
    {
        return (bool) preg_match('/(durante|dopo) un riposo breve/u', mb_strtolower((string) $this->uses_text));
    }

    /** Si ripristina con questo riposo, al livello che ha il personaggio in quella classe? */
    public function rechargesOn(RestType $rest, int $classLevel): bool
    {
        return match ($rest) {
            RestType::Long => $this->recharge !== null,
            RestType::Short => $this->recharge === RestType::Short
                || ($this->short_rest_from_level !== null && $classLevel >= $this->short_rest_from_level),
        };
    }
}
