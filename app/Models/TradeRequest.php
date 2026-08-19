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
 * Una richiesta di scambio: si chiede a parole una cosa che non si vede.
 *
 * Serve perché lo zaino di un altro non è pubblico e in vetrina finisce solo
 * quello che il proprietario ci mette. Per il resto si chiede — e quello che si
 * chiede è **un nome scritto a mano**, che può essere sbagliato: è una diceria,
 * non un riferimento a una riga.
 *
 * Non muove niente. Quando chi la riceve dice di sì nasce uno `Trade`, e da lì
 * in poi valgono le regole di sempre, vigilanza compresa.
 *
 * Gli stati sono quelli degli scambi (`TradeStatus`): sono le stesse quattro
 * risposte, e inventarne quattro uguali con un altro nome vorrebbe dire
 * tenerle allineate a mano per sempre.
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
     * I nomi degli oggetti offerti.
     *
     * Sono nomi e non righe di inventario: fra la richiesta e la risposta chi ha
     * offerto può averli venduti, e il controllo vero si fa quando lo scambio
     * si esegue.
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
