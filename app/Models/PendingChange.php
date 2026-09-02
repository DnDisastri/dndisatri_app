<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
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
    'character_id', 'requested_by', 'type', 'diff', 'summary',
    'grant_gp', 'grant_items', 'base_updated_at', 'archived_at',
])]
class PendingChange extends Model
{
    use HasFactory, LogsActivity;

    /** Vedi la nota in Trade: il predefinito del database non basta. */
    protected $attributes = ['status' => PendingChangeStatus::Pending->value];

    /**
     * Chi approva e chi rifiuta resta tracciato due volte: sulla richiesta
     * stessa (`reviewed_by`) e nel registro attività. La prima serve a
     * mostrarlo in bacheca, il secondo a ricostruire la sequenza.
     */
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

    /** Chi ha approvato o rifiutato: la bacheca è condivisa, la traccia no. */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === PendingChangeStatus::Pending;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Si archivia solo una richiesta **decisa**. Una ancora in attesa è viva:
     * nasconderla vorrebbe dire perderla di vista proprio mentre aspetta un sì
     * o un no.
     */
    public function isArchivable(): bool
    {
        return ! $this->isArchived() && ! $this->isPending();
    }

    /**
     * Il personaggio è cambiato fra la proposta e adesso.
     *
     * Non blocca l'approvazione: serve ad avvisare chi decide che la scheda si
     * è mossa nel frattempo, invece di sovrascrivere in silenzio come faceva
     * la vecchia applicazione. I bottini non sono mai obsoleti, perché si
     * applicano come somma e non come sostituzione.
     */
    public function isStale(): bool
    {
        if ($this->type->appliesAsDelta() || $this->base_updated_at === null) {
            return false;
        }

        return $this->character->updated_at?->gt($this->base_updated_at) ?? false;
    }

    /**
     * Il confronto campo per campo fra la scheda com'è adesso e come
     * diventerebbe. Il «prima» si legge dal personaggio in questo momento,
     * perché in archivio c'è solo il diff.
     *
     * @return Collection<int, array{label: string, before: string, after: string}>
     */
    public function diffRows(): Collection
    {
        $character = $this->character;

        return collect($this->diff ?? [])
            // La foto non è testo: si guarda, non si legge in una colonna.
            ->reject(fn ($after, $field) => $field === 'photo_path')
            ->map(fn ($after, $field) => [
                'label' => self::fieldLabel($field),
                'before' => self::readable($character?->getAttribute($field)),
                'after' => self::readable($after),
            ])
            ->values();
    }

    /** Il percorso (disco privato) della foto proposta, se la richiesta ne ha una. */
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

    private static function readable(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => 'Vuoto',
            is_bool($value) => $value ? 'sì' : 'no',
            is_array($value) => collect($value)
                ->filter(fn ($v) => $v !== false && $v !== 'none' && $v !== null)
                ->map(fn ($v, $k) => is_bool($v) ? $k : "{$k}: {$v}")
                ->join(', ') ?: 'Vuoto',
            default => (string) $value,
        };
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

    /** Le richieste che questo utente può vedere in bacheca. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isDm() || $user->isAdmin()) {
            return;
        }

        $query->where('requested_by', $user->getKey());
    }
}
