<?php

namespace App\Models;

use App\Domain\Dnd\Coins;
use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** Un'azione di mercato in attesa di via libera, chiesta da un giocatore sotto richiamo. */
#[Fillable(['user_id', 'warning_id', 'type', 'payload', 'summary'])]
class SupervisedAction extends Model
{
    use HasFactory, LogsActivity;

    /** Vedi la nota in Trade: il predefinito del database non basta. */
    protected $attributes = ['status' => PendingChangeStatus::Pending->value];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vigilanza')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => SupervisedActionType::class,
            'status' => PendingChangeStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === PendingChangeStatus::Pending;
    }

    /**
     * I personaggi coinvolti, da qualunque lato: serve al conflitto d'interessi
     * (un DM non approva uno scambio con dentro un suo personaggio).
     *
     * @return list<int>
     */
    public function involvedCharacterIds(): array
    {
        $payload = $this->payload ?? [];

        return collect([
            $payload['from_character_id'] ?? null,
            $payload['to_character_id'] ?? null,
            $payload['character_id'] ?? null,
            $payload['seller_character_id'] ?? null,
            $payload['buyer_character_id'] ?? null,
        ])->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Sul modello e non nella pagina, così si prova senza browser.
     *
     * @return list<array{voce: string, valore: string}>
     */
    public function details(): array
    {
        $payload = $this->payload ?? [];
        $nome = fn (?int $id) => $id === null ? null : (Character::find($id)?->name ?? "personaggio #{$id}");

        $righe = match ($this->type) {
            SupervisedActionType::TradeProposal => [
                ['voce' => 'Da', 'valore' => $nome($payload['from_character_id'] ?? null)],
                ['voce' => 'A', 'valore' => $nome($payload['to_character_id'] ?? null)],
                ['voce' => 'Offre', 'valore' => self::roba($payload['give'] ?? [], (int) ($payload['give_cp'] ?? 0))],
                ['voce' => 'Chiede', 'valore' => self::roba($payload['want'] ?? [], (int) ($payload['want_cp'] ?? 0))],
                ['voce' => 'Messaggio', 'valore' => $payload['message'] ?? null],
            ],
            SupervisedActionType::TradeAcceptance => [
                ['voce' => 'Scambio proposto da', 'valore' => $nome($payload['from_character_id'] ?? null)],
                ['voce' => 'Che accetterebbe', 'valore' => $nome($payload['to_character_id'] ?? null)],
            ],
            SupervisedActionType::ListingCreation => [
                ['voce' => 'Chi vende', 'valore' => $nome($payload['character_id'] ?? null)],
                ['voce' => 'Cosa', 'valore' => trim(($payload['qty'] ?? 1).'× '.($payload['name'] ?? 'Vuoto'))],
                ['voce' => 'Prezzo', 'valore' => isset($payload['price_cp']) ? Coins::formatValue((int) $payload['price_cp']) : null],
            ],
            SupervisedActionType::ListingPurchase => [
                ['voce' => 'Chi compra', 'valore' => $nome($payload['buyer_character_id'] ?? null)],
                ['voce' => 'Da chi', 'valore' => $nome($payload['seller_character_id'] ?? null)],
            ],
        };

        return array_values(array_filter($righe, fn (array $riga) => filled($riga['valore'])));
    }

    /** @param  list<array{name: string, qty?: int}>  $items */
    private static function roba(array $items, int $cp): string
    {
        $pezzi = collect($items)
            ->map(fn (array $item) => ($item['qty'] ?? 1).'× '.($item['name'] ?? '?'))
            ->all();

        if ($cp > 0) {
            $pezzi[] = Coins::formatValue($cp);
        }

        return $pezzi === [] ? 'niente' : implode(', ', $pezzi);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PendingChangeStatus::Pending);
    }

    /** Quelle che questo utente può vedere: le proprie, o tutte se vigila. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isDm() || $user->isAdmin()) {
            return;
        }

        $query->where('user_id', $user->getKey());
    }
}
