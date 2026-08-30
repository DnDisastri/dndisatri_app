<?php

namespace App\Models;

use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['from_character_id', 'to_character_id', 'give_gp', 'want_gp', 'message'])]
class Trade extends Model
{
    use HasFactory;

    /**
     * Lo stato iniziale va dichiarato anche qui, non solo come valore
     * predefinito della colonna: un modello appena creato non rilegge la riga,
     * quindi `status` resterebbe null e `isOpen()` fallirebbe su un oggetto che
     * il database considera già in attesa.
     */
    protected $attributes = ['status' => TradeStatus::Pending->value];

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'give_gp' => 'integer',
            'reversed_at' => 'datetime',
            'want_gp' => 'integer',
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
     * Perché questo scambio non si può eseguire **adesso**, se non si può (P28).
     *
     * Negli scambi niente esce dall'inventario al momento della proposta: la
     * disponibilità si verifica all'accettazione, e per **entrambe** le parti.
     * Fra la proposta e la risposta il mondo si muove — l'oggetto venduto,
     * l'oro speso — e allora l'accettazione fallisce. Questo lo dice prima del
     * clic, invece che dopo con un errore rosso.
     *
     * È la stessa verifica di `AcceptTrade::assertCanDeliver`, in sola lettura.
     * Le due non devono discordare: se la card dice «si può» e l'accettazione
     * poi fallisce, o viceversa, è peggio del silenzio. Se un giorno le regole
     * dello scambio cambiano, vanno cambiate in tutte e due.
     *
     * @return list<string>
     */
    public function deliveryProblems(): array
    {
        $problemi = [];

        $controlla = function (?Character $chi, Collection $oggetti, int $oro) use (&$problemi) {
            if ($chi === null) {
                return;
            }

            if ($chi->gp < $oro) {
                $problemi[] = "{$chi->name} non ha abbastanza oro ({$chi->gp}/{$oro} mo)";
            }

            foreach ($oggetti as $item) {
                if (! $chi->ownsItem($item->name, $item->qty)) {
                    $problemi[] = "{$chi->name} non ha più {$item->qty}× {$item->name}";
                }
            }
        };

        $controlla($this->from, $this->givenItems(), $this->give_gp);
        $controlla($this->to, $this->wantedItems(), $this->want_gp);

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
