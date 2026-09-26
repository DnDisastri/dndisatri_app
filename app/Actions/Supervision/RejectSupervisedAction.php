<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Enums\PendingChangeStatus;
use App\Models\SupervisedAction;
use App\Models\User;
use App\Notifications\SupervisedActionDecided;
use InvalidArgumentException;
use RuntimeException;

/**
 * Il blocco di un'azione controllata.
 *
 * **La spiegazione è obbligatoria**, ed è l'unica cosa obbligatoria di tutto il
 * sistema delle decisioni: una richiesta di gioco si può rifiutare senza dire
 * niente, questa no.
 *
 * La ragione è che qui il giocatore è già sotto richiamo. Un blocco senza
 * motivo, a chi si sente osservato, è il modo più rapido di trasformare una
 * misura di controllo in un sospetto — e il gruppo gioca insieme il sabato
 * sera.
 */
final class RejectSupervisedAction
{
    public function handle(SupervisedAction $action, User $reviewer, string $note): SupervisedAction
    {
        if (blank($note)) {
            throw new InvalidArgumentException(
                'Serve una spiegazione: il giocatore deve sapere perché è stato bloccato.'
            );
        }

        if (! $action->isPending()) {
            throw new RuntimeException('Questa richiesta è già stata decisa.');
        }

        $action->forceFill([
            'status' => PendingChangeStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        // Non è successo niente al mercato: l'intenzione non era mai stata
        // eseguita, quindi non c'è nulla da riportare indietro.
        $action->user()->first()?->notify(new SupervisedActionDecided($action));

        return $action;
    }
}
