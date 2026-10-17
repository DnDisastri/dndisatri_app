<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\ClassCatalogue;
use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una classe giocabile, con tutto quello che serve a creazione, livelli,
 * incantesimi e multiclasse. I privilegi livello per livello restano in
 * config/dnd/features.php. I personaggi la indicano per nome: il nome non si
 * cambia dal pannello.
 */
#[Fillable([
    'name', 'rules_edition', 'hit_die', 'saves', 'skill_count', 'skill_choices', 'proficiencies', 'equipment_summary',
    'caster_type', 'spell_ability', 'cantrips_at_1', 'spells_at_1', 'prepares', 'prepare_from_level', 'prepare_half_level',
    'swaps_spells', 'copies_scrolls', 'spell_list', 'subclass_level',
    'multiclass_requirements', 'multiclass_skill_count', 'multiclass_skill_choices', 'starting_equipment', 'position',
])]
class GameClass extends Model
{
    use LogsActivity;

    protected $attributes = [
        'rules_edition' => '2014', 'skill_count' => 2, 'caster_type' => 'none', 'cantrips_at_1' => 0,
        'prepares' => false, 'prepare_from_level' => 1, 'prepare_half_level' => false,
        'swaps_spells' => false, 'copies_scrolls' => false, 'subclass_level' => 3,
        'multiclass_skill_count' => 0, 'position' => 99,
    ];

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
            'rules_edition' => RulesEdition::class,
            'hit_die' => 'integer',
            'saves' => 'array',
            'skill_count' => 'integer',
            'skill_choices' => 'array',
            'caster_type' => CasterType::class,
            'spell_ability' => Ability::class,
            'cantrips_at_1' => 'integer',
            'spells_at_1' => 'integer',
            'prepares' => 'boolean',
            'prepare_from_level' => 'integer',
            'prepare_half_level' => 'boolean',
            'swaps_spells' => 'boolean',
            'copies_scrolls' => 'boolean',
            'spell_list' => 'array',
            'subclass_level' => 'integer',
            'multiclass_requirements' => 'array',
            'multiclass_skill_count' => 'integer',
            'multiclass_skill_choices' => 'array',
            'starting_equipment' => 'array',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(ClassCatalogue::class));
        static::deleted(fn () => app()->forgetInstance(ClassCatalogue::class));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Personaggi, sottoclassi o build che la usano: finché ci sono non si cancella. */
    public function isInUse(): bool
    {
        return CharacterClass::where('class', $this->name)->exists()
            || Character::withTrashed()->where('class', $this->name)->exists()
            || Subclass::where('class', $this->name)->exists()
            || Build::where('class', $this->name)->exists();
    }
}
