<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Exceptions\QuestUnavailableException;
use App\Models\GameSession;
use App\Models\Quest;
use App\Notifications\QuestScheduled;
use InvalidArgumentException;

/**
 * Mettere una quest in una sessione della sua campagna, o toglierla.
 * Chi aveva detto «mi interessa» riceve l'avviso solo quando la sessione cambia.
 */
final class ScheduleQuest
{
    public function handle(Quest $quest, ?GameSession $session): Quest
    {
        if (! $quest->isActive()) {
            throw QuestUnavailableException::notActive();
        }

        if ($session !== null && ($session->campaign_id !== $quest->campaign_id || ! $session->isUpcoming())) {
            throw new InvalidArgumentException('Scegli una sessione in programma della stessa campagna.');
        }

        $prima = $quest->game_session_id;
        $quest->forceFill(['game_session_id' => $session?->getKey()])->save();

        if ($session !== null && $prima !== $session->getKey()) {
            foreach ($quest->interested()->get() as $giocatore) {
                $giocatore->notify(new QuestScheduled($quest, $session));
            }
        }

        return $quest->fresh();
    }
}
