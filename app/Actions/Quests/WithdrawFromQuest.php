<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Quest;
use App\Models\User;

/**
 * Ritirarsi da una quest.
 *
 * Si può fare **sempre**, anche a serata già confermata: uno si ammala, e
 * costringerlo a restare iscritto non lo fa venire. Chi c'era davvero lo segna
 * il dungeon master a fine serata con le presenze, che sono un'altra cosa.
 *
 * La riga non si cancella, cambia stato: lo storico di chi voleva giocare
 * serve al dungeon master per capire se quella quest interessava a qualcuno.
 *
 * Da una quest conclusa non ci si toglie: l'archivio racconta com'è andata.
 */
final class WithdrawFromQuest
{
    public function handle(Quest $quest, User $user): Quest
    {
        if (! $quest->isActive()) {
            throw QuestUnavailableException::notActive();
        }

        if (! $quest->hasParticipant($user)) {
            throw QuestUnavailableException::notAParticipant();
        }

        $quest->participants()->updateExistingPivot($user, [
            'status' => QuestSeatStatus::Withdrawn->value,
            'decided_at' => now(),
        ]);

        return $quest->fresh();
    }
}
