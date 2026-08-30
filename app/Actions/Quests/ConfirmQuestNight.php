<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Quest;
use App\Notifications\QuestSeatConfirmed;
use Illuminate\Support\Facades\DB;

/**
 * «La serata si fa.»
 *
 * Un gesto solo, sulla quest: tutti i prenotati diventano confermati insieme.
 * Il dungeon master **non sceglie chi entra** — non c'è nessun rifiuto in
 * questo sistema, e chi si è prenotato ha il posto.
 *
 * Non controlla il minimo di proposito: quel numero dice a chi conduce se la
 * serata sta in piedi, ma la decisione resta sua. Un tavolo in due si può
 * fare, se chi lo conduce lo vuole.
 *
 * La lista d'attesa **non viene toccata**: chi è in attesa entra solo se un
 * posto si libera, e a quel punto lo pesca il dungeon master.
 */
final class ConfirmQuestNight
{
    public function handle(Quest $quest): Quest
    {
        if (! $quest->isActive()) {
            throw QuestUnavailableException::notActive();
        }

        return DB::transaction(function () use ($quest) {
            $prenotati = $quest->booked()->get();

            // La serata è dichiarata sulla quest, non dedotta dai posti: così
            // resta tale anche se poi si ritirano tutti.
            $quest->forceFill(['night_confirmed_at' => now()])->save();

            foreach ($prenotati as $giocatore) {
                $quest->participants()->updateExistingPivot($giocatore, [
                    'status' => QuestSeatStatus::Confirmed->value,
                    'decided_at' => now(),
                ]);

                $giocatore->notify(new QuestSeatConfirmed($quest));
            }

            return $quest->fresh();
        });
    }
}
