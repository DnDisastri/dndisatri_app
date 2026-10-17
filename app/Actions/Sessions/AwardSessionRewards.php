<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Market\GrantCoins;
use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\MagicItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Le ricompense di fine sessione (monete, oggetti o tutte e due), uguali per
 * tutti i personaggi segnati presenti, per i presenti di un tavolo o per uno solo.
 * Tutto o niente: una borsa che rifiuta annulla anche le altre. Ogni consegna
 * resta nella sessione, così si vede se sono già state date.
 */
final class AwardSessionRewards
{
    public function __construct(private readonly GrantCoins $grant) {}

    /**
     * @param  list<array{name: string, qty?: int, category?: ?string, value_cp?: int, details?: ?string, base?: ?string}>  $items  a testa
     * @return Collection<int, Character> chi le ha ricevute
     */
    public function handle(
        GameSession $session,
        User $actor,
        Coins $perHead,
        string $reason,
        ?int $tableId = null,
        ?int $characterId = null,
        array $items = [],
    ): Collection {
        $items = array_map(MagicItem::linkRow(...), $items);

        if ($perHead->hasNegative() || ($perHead->isEmpty() && $items === [])) {
            throw new InvalidArgumentException('Indica le monete a testa, gli oggetti o tutti e due.');
        }

        // Niente `when()`: passerebbe la query sottostante, che non conosce `wherePivot`.
        $relazione = $session->attendees();

        if ($tableId !== null) {
            $relazione->wherePivot('session_table_id', $tableId);
        }

        $presenti = $relazione->pluck('game_session_user.character_id')->filter();

        $personaggi = Character::query()
            ->alive()
            ->whereIn('id', $presenti)
            ->when($characterId !== null, fn ($q) => $q->whereKey($characterId))
            ->orderBy('name')
            ->get();

        if ($personaggi->isEmpty()) {
            throw new InvalidArgumentException('Prima segna chi c\'era, con il suo personaggio.');
        }

        $motivo = "{$reason}, {$session->displayTitle()}";

        $elenco = collect($items)->map(fn (array $item) => ((int) ($item['qty'] ?? 1)).'× '.$item['name'])->all();

        DB::transaction(function () use ($session, $actor, $perHead, $motivo, $reason, $personaggi, $tableId, $items, $elenco) {
            foreach ($personaggi as $personaggio) {
                if (! $perHead->isEmpty()) {
                    $this->grant->give($personaggio, $perHead, $actor, $motivo);
                }

                if ($items !== []) {
                    $this->giveItems($personaggio, $items);
                    $personaggio->refresh()->recordInLedger(LedgerAction::DmItems, 'Ricevuti '.implode(', ', $elenco)." ({$motivo})", actor: $actor);
                }
            }

            $session->forceFill([
                'rewards' => [
                    ...($session->rewards ?? []),
                    [
                        'coins' => $perHead->nonZero(),
                        'items' => $elenco,
                        'reason' => $reason,
                        'table' => $tableId === null ? null : $session->tables()->whereKey($tableId)->value('name'),
                        'by' => $actor->name,
                        'at' => now()->toIso8601String(),
                        'characters' => $personaggi->pluck('name')->all(),
                    ],
                ],
            ])->save();
        });

        return $personaggi;
    }

    /** @param  list<array<string, mixed>>  $items */
    private function giveItems(Character $personaggio, array $items): void
    {
        foreach ($items as $item) {
            $personaggio->addToInventory(...[
                'name' => $item['name'],
                'qty' => (int) ($item['qty'] ?? 1),
                'category' => $item['category'] ?? null,
                'valueCp' => (int) ($item['value_cp'] ?? 0),
                'details' => $item['details'] ?? null,
                ...MagicItem::inventoryExtras($item),
            ]);
        }
    }
}
