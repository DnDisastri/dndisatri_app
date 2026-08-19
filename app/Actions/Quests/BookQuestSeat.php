<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Prenotare un posto a una quest.
 *
 * Non è più un'iscrizione: il giocatore dichiara di volerci essere, e il posto
 * diventa suo quando il dungeon master conferma la serata. Se i posti sono
 * esauriti la prenotazione **non viene rifiutata**, diventa una riga in lista
 * d'attesa — sapere che in sei volevano giocare per quattro posti è
 * un'informazione, non un errore.
 *
 * La riga della quest viene bloccata per tutta la transazione: senza, due
 * giocatori che si prenotano nello stesso istante sull'ultimo posto passerebbero
 * entrambi il controllo e il tavolo finirebbe con un posto di troppo.
 */
final class BookQuestSeat
{
    public function handle(Quest $quest, User $user): QuestSeatStatus
    {
        return DB::transaction(function () use ($quest, $user) {
            $locked = Quest::whereKey($quest->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw QuestUnavailableException::notActive();
            }

            $attuale = $locked->seatOf($user);

            // Prenotarsi due volte non è un errore: è già dentro, e lo stato
            // che ha vale più di quello che chiederebbe adesso — un confermato
            // non deve tornare semplice prenotato.
            if ($attuale !== null && $attuale->isActive()) {
                return $attuale;
            }

            $nuovo = $locked->isFull() ? QuestSeatStatus::Waiting : QuestSeatStatus::Booked;

            // Chi si era ritirato e ci ripensa riusa la sua riga: lo storico è
            // uno per giocatore, e la chiave unica non ne ammette due.
            if ($attuale === null) {
                $locked->participants()->attach($user, [
                    'status' => $nuovo->value,
                    'joined_at' => now(),
                ]);
            } else {
                $locked->participants()->updateExistingPivot($user, [
                    'status' => $nuovo->value,
                    'joined_at' => now(),
                    'decided_at' => null,
                ]);
            }

            return $nuovo;
        });
    }
}
