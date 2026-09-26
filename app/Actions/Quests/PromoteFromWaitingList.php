<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Quest;
use App\Models\User;
use App\Notifications\QuestSeatConfirmed;
use Illuminate\Support\Facades\DB;

/**
 * Chiamare qualcuno dalla lista d'attesa.
 *
 * È l'unica decisione che il dungeon master prende **sul singolo giocatore**,
 * e serve solo quando un posto si libera. Chi era in attesa aveva già detto
 * «io ci sarei»: è la fila giusta da cui pescare, invece di riaprire una corsa
 * che qualcuno aveva già perso.
 *
 * Se la serata è già stata confermata il posto nasce confermato: sarebbe
 * assurdo chiamare qualcuno a una serata che si fa e lasciarlo «in forse».
 */
final class PromoteFromWaitingList
{
    public function handle(Quest $quest, User $user): QuestSeatStatus
    {
        return DB::transaction(function () use ($quest, $user) {
            $locked = Quest::whereKey($quest->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw QuestUnavailableException::notActive();
            }

            if ($locked->seatOf($user) !== QuestSeatStatus::Waiting) {
                throw QuestUnavailableException::notWaiting();
            }

            if ($locked->isFull()) {
                throw QuestUnavailableException::full();
            }

            $nuovo = $locked->isNightConfirmed()
                ? QuestSeatStatus::Confirmed
                : QuestSeatStatus::Booked;

            $locked->participants()->updateExistingPivot($user, [
                'status' => $nuovo->value,
                'decided_at' => now(),
            ]);

            if ($nuovo === QuestSeatStatus::Confirmed) {
                $user->notify(new QuestSeatConfirmed($locked));
            }

            return $nuovo;
        });
    }
}
