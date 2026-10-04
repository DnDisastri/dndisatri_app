<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\Coins;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'character_id', 'requested_by', 'type', 'diff', 'summary', 'note',
    'grant_coins', 'grant_items', 'base_updated_at', 'archived_at',
])]
class PendingChange extends Model
{
    use HasFactory, LogsActivity;

    /** Vedi la nota in Trade: il predefinito del database non basta. */
    protected $attributes = ['status' => PendingChangeStatus::Pending->value];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('richiesta')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => PendingChangeType::class,
            'status' => PendingChangeStatus::class,
            'diff' => 'array',
            'grant_coins' => 'array',
            'grant_items' => 'array',
            'base_updated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function grantCoins(): Coins
    {
        return Coins::fromArray($this->grant_coins);
    }

    public function isPending(): bool
    {
        return $this->status === PendingChangeStatus::Pending;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Una richiesta in attesa non si archivia: sparirebbe dalla bacheca. */
    public function isArchivable(): bool
    {
        return ! $this->isArchived() && ! $this->isPending();
    }

    /** Avvisa senza bloccare. I bottini non sono mai obsoleti: si sommano. */
    public function isStale(): bool
    {
        if ($this->type->appliesAsDelta() || $this->base_updated_at === null) {
            return false;
        }

        return $this->character->updated_at?->gt($this->base_updated_at) ?? false;
    }

    /** Chiavi del diff che non sono colonne della scheda: senza un «prima» da confrontare. */
    private const NON_COLONNE = ['photo_path', 'class_up', 'feat', 'spells'];

    /**
     * Il «prima» si legge dal personaggio adesso: la richiesta salva solo il diff.
     *
     * @return Collection<int, array{label: string, before: string, after: string}>
     */
    public function diffRows(): Collection
    {
        $character = $this->character;

        return collect($this->diff ?? [])
            ->reject(fn ($after, $field) => in_array($field, self::NON_COLONNE, true))
            ->map(fn ($after, $field) => [
                'label' => self::fieldLabel($field),
                'before' => self::readable($character?->getAttribute($field)),
                'after' => self::readable($after),
            ])
            ->values();
    }

    public function proposedPhotoPath(): ?string
    {
        $path = $this->diff['photo_path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Nome',
            'class' => 'Classe',
            'subclass' => 'Sottoclasse',
            'race' => 'Specie',
            'background' => 'Background',
            'story' => 'Storia',
            'photo_path' => 'Foto',
            'level' => 'Livello',
            'hit_die' => 'Dado vita',
            'hp_max' => 'PF massimi',
            'hp_current' => 'PF attuali',
            'hp_temp' => 'PF temporanei',
            'speed' => 'Velocità',
            'notes' => 'Note',
            'skills' => 'Abilità',
            'saving_throws' => 'Tiri salvezza',
            'species_traits' => 'Tratti di specie',
            'class_features' => 'Privilegi di classe',
            'subclass_features' => 'Privilegi di sottoclasse',
            'background_feature' => 'Privilegio del background',
            'class_up' => 'Classe',
            'spell_ability' => 'Caratteristica da incantatore',
            default => Ability::tryFrom($field)?->fullName() ?? Str::headline($field),
        };
    }

    /** Ricorsiva: un array annidato diventa testo invece di rompere la pagina. */
    private static function readable(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => 'Vuoto',
            is_bool($value) => $value ? 'sì' : 'no',
            is_array($value) => self::readableArray($value),
            default => (string) $value,
        };
    }

    /** @param  array<array-key,mixed>  $value */
    private static function readableArray(array $value): string
    {
        // In una lista gli indici 0, 1, 2 non si scrivono.
        $lista = array_is_list($value);

        return collect($value)
            ->filter(fn ($v) => $v !== false && $v !== 'none' && $v !== null)
            ->map(fn ($v, $k) => match (true) {
                $lista => self::readable($v),
                is_bool($v) => (string) $k,
                default => "{$k}: ".self::readable($v),
            })
            ->join(', ') ?: 'Vuoto';
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PendingChangeStatus::Pending);
    }

    public function scopeDecided(Builder $query): void
    {
        $query->whereIn('status', [PendingChangeStatus::Approved, PendingChangeStatus::Rejected]);
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isDm() || $user->isAdmin()) {
            return;
        }

        $query->where('requested_by', $user->getKey());
    }
}
