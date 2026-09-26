<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Una richiesta di scambio: si chiede a parole una cosa che non si vede (lo
 * zaino di un altro non è pubblico). Quello che si chiede è un nome scritto a
 * mano, che può essere sbagliato.
 *
 * Non muove niente: quando chi la riceve dice di sì nasce uno `Trade`. Gli stati
 * sono quelli degli scambi (`TradeStatus`), per non tenerne due serie allineate.
 */
#[Fillable(['from_character_id', 'to_character_id', 'wanted', 'offered', 'offered_gp', 'message'])]
class TradeRequest extends Model
{
    use HasFactory;

    /** Come per `Trade`: un modello appena creato non rilegge la riga. */
    protected $attributes = ['status' => TradeStatus::Pending->value];

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'offered' => 'array',
            'offered_gp' => 'integer',
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

    /** Lo scambio nato da questa richiesta, se è stata accettata. */
    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * I nomi degli oggetti offerti: nomi, non righe d'inventario (chi ha offerto
     * può averli venduti; il controllo vero si fa quando lo scambio si esegue).
     *
     * @return Collection<int,string>
     */
    public function offeredNames(): Collection
    {
        return collect($this->offered ?? [])->filter()->values();
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', TradeStatus::Pending);
    }

    /** Le richieste che aspettano una risposta da questo personaggio. */
    public function scopeAwaiting(Builder $query, Character $character): void
    {
        $query->where('to_character_id', $character->getKey())
            ->where('status', TradeStatus::Pending);
    }
}
