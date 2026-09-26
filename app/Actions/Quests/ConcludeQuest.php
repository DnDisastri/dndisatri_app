<?php

declare(strict_types=1);

namespace App\Actions\Quests;

use App\Enums\QuestOutcome;
use App\Exceptions\QuestUnavailableException;
use App\Models\Quest;
use InvalidArgumentException;

/**
 * Porta una quest fuori dallo stato attivo: completata (andata a buon fine) o
 * chiusa (abbandonata).
 *
 * Entrambe sono irreversibili, ed è il motivo per cui passano da qui e non da
 * un `update()`: `completed_at` e `closed_at` non sono mass-assignable, quindi
 * non esiste nessun'altra strada per scriverle.
 *
 * L'esito scritto viaggia insieme alla data e non a parte: si racconta com'è
 * andata **mentre** si chiude, non in un secondo momento che non arriva mai.
 * Per la stessa ragione `outcome_notes` resta fuori da `#[Fillable]`.
 */
final class ConcludeQuest
{
    public function handle(Quest $quest, QuestOutcome $outcome, ?string $notes = null): Quest
    {
        if ($outcome === QuestOutcome::Active) {
            throw new InvalidArgumentException(
                'Una quest non torna attiva: completata e chiusa sono definitive.'
            );
        }

        if (! $quest->isActive()) {
            throw QuestUnavailableException::notActive();
        }

        $column = $outcome === QuestOutcome::Completed ? 'completed_at' : 'closed_at';

        $quest->forceFill([
            $column => now(),
            'outcome_notes' => filled($notes) ? trim($notes) : null,
        ])->save();

        return $quest;
    }
}
