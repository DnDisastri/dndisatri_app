<?php

namespace App\Models;

use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Un'azione di mercato in attesa di via libera, chiesta da un giocatore sotto
 * richiamo (D13).
 */
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
     * I personaggi coinvolti, da qualunque lato.
     *
     * Serve alla regola del conflitto d'interessi: un DM non dà il via libera a
     * uno scambio in cui c'è dentro un suo personaggio, esattamente come non
     * approva una richiesta del proprio.
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
     * L'intenzione scritta in italiano, riga per riga.
     *
     * Il riassunto («Vende Spada lunga +1 per 40 mo») basta a decidere quali
     * aprire, non a decidere: per quello bisogna vedere **cosa esce e cosa
     * entra, e da quale personaggio**. Il `payload` è un array grezzo con degli
     * id dentro, e mostrarlo così vorrebbe dire chiedere a chi vigila di fare
     * l'interprete.
     *
     * Sta sul modello e non nella pagina perché è una lettura dei dati, non un
     * fatto di grafica — e perché così si può provare senza aprire un browser.
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
                ['voce' => 'Offre', 'valore' => self::roba($payload['give'] ?? [], (int) ($payload['give_gp'] ?? 0))],
                ['voce' => 'Chiede', 'valore' => self::roba($payload['want'] ?? [], (int) ($payload['want_gp'] ?? 0))],
                ['voce' => 'Messaggio', 'valore' => $payload['message'] ?? null],
            ],
            SupervisedActionType::TradeAcceptance => [
                ['voce' => 'Scambio proposto da', 'valore' => $nome($payload['from_character_id'] ?? null)],
                ['voce' => 'Che accetterebbe', 'valore' => $nome($payload['to_character_id'] ?? null)],
            ],
            SupervisedActionType::ListingCreation => [
                ['voce' => 'Chi vende', 'valore' => $nome($payload['character_id'] ?? null)],
                ['voce' => 'Cosa', 'valore' => trim(($payload['qty'] ?? 1).'× '.($payload['name'] ?? '—'))],
                ['voce' => 'Prezzo', 'valore' => isset($payload['price']) ? $payload['price'].' mo' : null],
            ],
            SupervisedActionType::ListingPurchase => [
                ['voce' => 'Chi compra', 'valore' => $nome($payload['buyer_character_id'] ?? null)],
                ['voce' => 'Da chi', 'valore' => $nome($payload['seller_character_id'] ?? null)],
            ],
        };

        // Le voci vuote non si mostrano: una riga «Messaggio: —» occupa spazio
        // per dire che non c'è niente da leggere.
        return array_values(array_filter($righe, fn (array $riga) => filled($riga['valore'])));
    }

    /**
     * Oggetti e oro di un lato dello scambio, in una riga sola.
     *
     * @param  list<array{name: string, qty?: int}>  $items
     */
    private static function roba(array $items, int $gp): string
    {
        $pezzi = collect($items)
            ->map(fn (array $item) => ($item['qty'] ?? 1).'× '.($item['name'] ?? '?'))
            ->all();

        if ($gp > 0) {
            $pezzi[] = "{$gp} mo";
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
