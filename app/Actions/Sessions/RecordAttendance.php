<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\Character;
use App\Models\GameSession;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Segna chi c'era davvero a una serata, e con quale personaggio.
 *
 * Sostituisce l'elenco invece di aggiungere: il DM spunta i presenti a fine
 * sessione e quella è la lista definitiva, comprese le correzioni.
 *
 * Non c'è nessun vincolo di iscrizione a una quest: chi si presenta senza
 * essersi iscritto viene segnato lo stesso, che è come vanno le serate.
 *
 * **Il personaggio è facoltativo.** Il DM che conduce c'era senza giocare, un
 * ospite pure, e le presenze registrate prima che questa colonna esistesse non
 * possono inventarselo. Quello che invece non si può fare è attribuire a un
 * giocatore il personaggio di un altro: è l'unico controllo qui dentro, e
 * serve perché la scelta arriva da una tendina e le tendine si manomettono.
 */
final class RecordAttendance
{
    /**
     * Due forme, entrambe valide:
     *
     * - `[3, 7]` — solo i giocatori, senza dire con che cosa giocavano;
     * - `[3 => 12, 7 => null]` — giocatore => personaggio, che è quello che
     *   manda la pagina delle presenze.
     *
     * @param  Collection<int,mixed>|array<int,mixed>  $attendance
     */
    public function handle(GameSession $session, Collection|array $attendance): GameSession
    {
        $pairs = $this->normalise($attendance);

        $this->assertCharactersBelong($pairs);

        $session->attendees()->sync(
            $pairs->map(fn (?int $characterId) => ['character_id' => $characterId])->all()
        );

        return $session->load('attendees');
    }

    /**
     * Riduce le due forme a una sola: giocatore => personaggio o null.
     *
     * @param  Collection<int,mixed>|array<int,mixed>  $attendance
     * @return Collection<int,int|null>
     */
    private function normalise(Collection|array $attendance): Collection
    {
        $raw = $attendance instanceof Collection ? $attendance->all() : $attendance;

        // Un elenco semplice sono id di giocatori: nessuno ha un personaggio.
        if (array_is_list($raw)) {
            return collect($raw)
                ->unique()
                ->mapWithKeys(fn ($userId) => [(int) $userId => null]);
        }

        return collect($raw)->mapWithKeys(fn ($characterId, $userId) => [
            (int) $userId => $characterId === null ? null : (int) $characterId,
        ]);
    }

    /** @param  Collection<int,int|null>  $pairs */
    private function assertCharactersBelong(Collection $pairs): void
    {
        $claimed = $pairs->filter()->all();

        if ($claimed === []) {
            return;
        }

        $owners = Character::whereIn('id', $claimed)->pluck('user_id', 'id');

        foreach ($claimed as $userId => $characterId) {
            if (($owners[$characterId] ?? null) !== $userId) {
                throw new InvalidArgumentException(
                    'Quel personaggio non è di quel giocatore.'
                );
            }
        }
    }
}
