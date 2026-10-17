<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemCatalogue;
use App\Enums\ArmorType;
use App\Enums\DamageType;
use App\Enums\EquipmentSlot;
use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un'arma, un'armatura o uno scudo del catalogo: i numeri che usano attacchi e
 * CA. Gli oggetti degli zaini lo indicano per nome (`name` o `base`).
 */
#[Fillable([
    'name', 'kind', 'rules_edition', 'damage', 'damage_type', 'ability', 'properties',
    'armor_type', 'armor_base', 'dex_cap', 'stealth_disadvantage', 'strength_required',
    'shield_bonus', 'price_cp', 'position',
])]
class BaseItem extends Model
{
    use LogsActivity;

    protected $attributes = ['rules_edition' => '2014', 'stealth_disadvantage' => false, 'position' => 99];

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
            'kind' => EquipmentSlot::class,
            'rules_edition' => RulesEdition::class,
            'damage_type' => DamageType::class,
            'ability' => Ability::class,
            'properties' => 'array',
            'armor_type' => ArmorType::class,
            'armor_base' => 'integer',
            'dex_cap' => 'integer',
            'stealth_disadvantage' => 'boolean',
            'strength_required' => 'integer',
            'shield_bonus' => 'integer',
            'price_cp' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $item) {
            // Gli oggetti la indicano per nome: rinominando, li si porta dietro.
            if ($item->wasChanged('name') && ($vecchio = $item->getOriginal('name')) !== null) {
                foreach (['character_items', 'market_items'] as $tabella) {
                    DB::table($tabella)->where('base', $vecchio)->update(['base' => $item->name]);
                    DB::table($tabella)->whereNull('base')->where('name', $vecchio)->update(['base' => $item->name]);
                }

                DB::table('magic_items')->where('base', $vecchio)->update(['base' => $item->name]);
            }

            app()->forgetInstance(ItemCatalogue::class);
        });

        static::deleted(fn () => app()->forgetInstance(ItemCatalogue::class));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Accurata: si usa la migliore fra Forza e Destrezza. */
    public function isFinesse(): bool
    {
        return collect($this->properties ?? [])->contains(fn (string $p) => str_starts_with(mb_strtolower($p), 'accurata'));
    }

    /** Gli oggetti che lo indicano (zaini, Emporio, oggetti magici): finché ci sono non si cancella. */
    public function isInUse(): bool
    {
        foreach (['character_items', 'market_items'] as $tabella) {
            $usato = DB::table($tabella)
                ->where(fn ($q) => $q->where('base', $this->name)->orWhere(fn ($q) => $q->whereNull('base')->where('name', $this->name)))
                ->exists();

            if ($usato) {
                return true;
            }
        }

        return DB::table('magic_items')->where('base', $this->name)->exists();
    }

    /** In una riga: «1d8 taglienti · Versatile (1d10)», «CA 14 + DES (max 2)», «+2 CA». */
    public function summary(): string
    {
        return match ($this->kind) {
            EquipmentSlot::Weapon => collect([
                trim($this->damage.' '.$this->damage_type?->label()),
                ...($this->properties ?? []),
            ])->filter()->join(' · '),
            EquipmentSlot::Armor => 'CA '.$this->armor_base.match ($this->dex_cap) {
                null => ' + DES',
                0 => '',
                default => " + DES (max {$this->dex_cap})",
            }.($this->stealth_disadvantage ? ' · svantaggio in Furtività' : '')
                .($this->strength_required ? " · FOR {$this->strength_required}" : ''),
            EquipmentSlot::Shield => '+'.$this->shield_bonus.' CA',
        };
    }
}
