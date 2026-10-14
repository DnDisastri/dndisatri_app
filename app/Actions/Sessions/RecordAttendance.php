<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\Character;
use App\Models\GameSession;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Segna chi c'era a una sessione, e con quale personaggio. Sostituisce l'elenco:
 * quello che si salva è la lista definitiva. Vale anche per chi non era prenotato.
 *
 * Il personaggio è facoltativo; non può però essere di un altro giocatore
 * (la tendina si manomette). Gli ospiti senza account stanno sul loro posto.
 */
final class RecordAttendance
{
    /**
     * `[3, 7]` (solo giocatori) o `[3 => 12, 7 => null]` (giocatore => personaggio).
     * Nelle sessioni con i tavoli, `$tables` dice a quale tavolo ha giocato ognuno.
     *
     * @param  Collection<int,mixed>|array<int,mixed>  $attendance
     * @param  array<int, int|null>  $tables  giocatore => tavolo
     */
    public function handle(GameSession $session, Collection|array $attendance, array $tables = []): GameSession
    {
        $pairs = $this->normalise($attendance);

        $this->assertCharactersBelong($pairs);
        $this->assertTablesBelong($session, $tables);

        $session->attendees()->sync(
            $pairs->map(fn (?int $characterId, int $userId) => [
                'character_id' => $characterId,
                'session_table_id' => isset($tables[$userId]) ? (int) $tables[$userId] : null,
            ])->all()
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

    /** @param  array<int, int|null>  $tables */
    private function assertTablesBelong(GameSession $session, array $tables): void
    {
        $scelti = array_filter($tables);

        if ($scelti !== [] && $session->tables()->whereKey($scelti)->count() !== count(array_unique($scelti))) {
            throw new InvalidArgumentException('Quel tavolo non è di questa sessione.');
        }
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
