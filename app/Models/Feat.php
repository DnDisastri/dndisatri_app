<?php

namespace App\Models;

use App\Enums\RulesEdition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Il catalogo dei talenti fra cui scegliere salendo di livello o in creazione.
 *
 * Il personaggio ne tiene una copia (`character_feats`): cambiare il testo qui
 * non riscrive le schede.
 */
#[Fillable(['name', 'rules_edition', 'category', 'prerequisite', 'description', 'is_homebrew'])]
class Feat extends Model
{
    use HasFactory, LogsActivity;

    /** Le categorie del 2024: d'origine (background e Umano), generali, stili di combattimento, doni epici. */
    public const ORIGIN = 'origin';

    public const CATEGORIES = [
        self::ORIGIN => 'Di origine',
        'general' => 'Generale',
        'fighting_style' => 'Stile di combattimento',
        'epic_boon' => 'Dono epico',
    ];

    protected $attributes = ['rules_edition' => '2014', 'is_homebrew' => true];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('talenti')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'rules_edition' => RulesEdition::class,
            'is_homebrew' => 'boolean',
        ];
    }

    public function scopeOfEdition(Builder $query, RulesEdition $edition): void
    {
        $query->where('rules_edition', $edition->value)->orderBy('name');
    }

    /**
     * Il talento scelto, solo fra quelli del catalogo di quelle regole: i
     * giocatori non ne inventano, li aggiungono DM e admin dal pannello.
     *
     * @return array{name: string, description: ?string, prerequisite: ?string}|null
     */
    public static function pick(RulesEdition $edition, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $feat = self::ofEdition($edition)->whereKey($id)->first()
            ?? throw new InvalidArgumentException('Talento non trovato fra quelli di queste regole.');

        return ['name' => $feat->name, 'description' => $feat->description, 'prerequisite' => $feat->prerequisite];
    }
}
