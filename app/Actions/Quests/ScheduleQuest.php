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
 * Anche una sessione già giocata, per registrare il passato.
 * Chi aveva detto «mi interessa» riceve l'avviso solo quando la sessione cambia
 * ed è ancora da giocare: a una sessione passata non ci si può più prenotare.
 */
final class ScheduleQuest
{
    public function handle(Quest $quest, ?GameSession $session, bool $notify = true): Quest
    {
        if (! $quest->isActive()) {
            throw QuestUnavailableException::notActive();
        }

        if ($session !== null && $session->campaign_id !== $quest->campaign_id) {
            throw new InvalidArgumentException('Scegli una sessione della stessa campagna.');
        }

        $prima = $quest->game_session_id;
        $quest->forceFill(['game_session_id' => $session?->getKey()])->save();

        if ($notify && $session !== null && $session->isUpcoming() && $prima !== $session->getKey()) {
            foreach ($quest->interested()->get() as $giocatore) {
                $giocatore->notify(new QuestScheduled($quest, $session));
            }
        }

        return $quest->fresh();
    }
}
