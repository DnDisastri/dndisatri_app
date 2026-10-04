<?php

namespace App\Models;

use App\Domain\Dnd\Coins;
use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['from_character_id', 'to_character_id', 'give_cp', 'want_cp', 'message'])]
class Trade extends Model
{
    use HasFactory;

    /** Anche qui, non solo come default della colonna: un modello appena creato non rilegge la riga. */
    protected $attributes = ['status' => TradeStatus::Pending->value];

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'give_cp' => 'integer',
            'reversed_at' => 'datetime',
            'want_cp' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'from_character_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'to_character_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    /** Gli oggetti offerti da chi propone. */
    public function givenItems(): Collection
    {
        return $this->itemsInDirection(TradeDirection::Give);
    }

    /** Gli oggetti chiesti in cambio. */
    public function wantedItems(): Collection
    {
        return $this->itemsInDirection(TradeDirection::Want);
    }

    private function itemsInDirection(TradeDirection $direction): Collection
    {
        return $this->relationLoaded('items')
            ? $this->items->where('direction', $direction)->values()
            : $this->items()->where('direction', $direction)->get();
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /**
     * La stessa verifica di `AcceptTrade::assertCanDeliver` in sola lettura:
     * le due vanno tenute allineate.
     *
     * @return list<string>
     */
    public function deliveryProblems(): array
    {
        $problemi = [];

        $controlla = function (?Character $chi, Collection $oggetti, int $monete) use (&$problemi) {
            if ($chi === null) {
                return;
            }

            if ($chi->purseValue() < $monete) {
                $problemi[] = "{$chi->name} non ha abbastanza monete (".Coins::formatValue($chi->purseValue())
                    .' su '.Coins::formatValue($monete).')';
            }

            foreach ($oggetti as $item) {
                if (! $chi->ownsItem($item->name, $item->qty)) {
                    $problemi[] = "{$chi->name} non ha più {$item->qty}× {$item->name}";
                }
            }
        };

        $controlla($this->from, $this->givenItems(), $this->give_cp);
        $controlla($this->to, $this->wantedItems(), $this->want_cp);

        return $problemi;
    }

    /** Si può accettare adesso: è ancora aperta, e tutte e due possono dare la loro parte. */
    public function canBeAccepted(): bool
    {
        return $this->isOpen() && $this->deliveryProblems() === [];
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', TradeStatus::Pending);
    }

    /** Le proposte che aspettano una risposta da questo personaggio. */
    public function scopeAwaiting(Builder $query, Character $character): void
    {
        $query->where('to_character_id', $character->getKey())
            ->where('status', TradeStatus::Pending);
    }
}
