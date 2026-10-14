<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\GameSession;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * I DM mettono a tavolo chi si è prenotato alla sessione speciale. Vale per
 * tutti i posti, ospiti compresi; un tavolo vuoto toglie l'assegnazione.
 */
final class AssignTables
{
    /** @param  array<int, int|null>  $assignments  posto => tavolo */
    public function handle(GameSession $session, array $assignments): void
    {
        $tavoli = $session->tables()->pluck('id')->all();
        $posti = $session->bookings()->pluck('id')->all();

        foreach ($assignments as $postoId => $tavoloId) {
            if (! in_array((int) $postoId, $posti, true)) {
                throw new InvalidArgumentException('Quel posto non è di questa sessione.');
            }

            if ($tavoloId !== null && ! in_array((int) $tavoloId, $tavoli, true)) {
                throw new InvalidArgumentException('Quel tavolo non è di questa sessione.');
            }
        }

        $perTavolo = collect($assignments)->map(fn ($t) => $t === null ? null : (int) $t)
            ->groupBy(fn ($t) => (string) $t, preserveKeys: true);

        DB::transaction(function () use ($session, $perTavolo) {
            foreach ($perTavolo as $tavoloId => $posti) {
                $session->bookings()->whereKey($posti->keys())->update(['session_table_id' => $tavoloId === '' ? null : (int) $tavoloId]);
            }
        });
    }
}
