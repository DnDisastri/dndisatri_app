<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Le sessioni giocate da un personaggio ritirato passano al nuovo dello stesso
 * giocatore: presenze, diario e prenotazioni passate. Le monete e gli oggetti no.
 */
final class TransferSessions
{
    /** Quante sessioni sono passate. */
    public function handle(Character $from, Character $to, User $actor): int
    {
        if (! $actor->isDm() && ! $actor->isAdmin()) {
            throw new RuntimeException('Solo un DM o un admin passa le sessioni.');
        }

        if (! $from->trashed() || $to->trashed() || $from->user_id !== $to->user_id) {
            throw new RuntimeException('Le sessioni passano da un personaggio ritirato a uno attivo dello stesso giocatore.');
        }

        return DB::transaction(function () use ($from, $to) {
            $presenze = DB::table('game_session_user')->where('character_id', $from->getKey())->update(['character_id' => $to->getKey()]);

            DB::table('session_journals')->where('character_id', $from->getKey())->update(['character_id' => $to->getKey()]);

            DB::table('game_session_bookings')->where('character_id', $from->getKey())
                ->whereIn('game_session_id', fn ($q) => $q->select('id')->from('game_sessions')->where('played_at', '<', now()))
                ->update(['character_id' => $to->getKey()]);

            return $presenze;
        });
    }
}
